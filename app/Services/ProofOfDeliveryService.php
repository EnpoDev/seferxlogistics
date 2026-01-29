<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProofOfDeliveryService
{
    protected const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    protected const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    protected const THUMBNAIL_WIDTH = 400;
    protected const FULL_WIDTH = 1200;

    /**
     * POD fotoğrafı yükle ve siparişe kaydet
     */
    public function uploadPhoto(
        Order $order,
        UploadedFile $photo,
        ?array $location = null,
        ?string $note = null
    ): array {
        // Validasyon
        $this->validatePhoto($photo);

        // Dosya yolunu oluştur
        $filename = $this->generateFilename($order, $photo);
        $directory = $this->getDirectory($order);

        // Fotoğrafı optimize et ve kaydet
        $path = $this->processAndSavePhoto($photo, $directory, $filename);

        // Siparişi güncelle
        $order->savePod($path, $location, $note);

        // Sipariş henüz teslim edilmediyse, otomatik teslim et
        if ($order->status === Order::STATUS_ON_DELIVERY) {
            $order->markDelivered();
            $order->updateCustomerStats();
        }

        return [
            'success' => true,
            'photo_url' => $order->getPodPhotoUrl(),
            'timestamp' => now()->format('d.m.Y H:i:s'),
        ];
    }

    /**
     * Fotoğrafı doğrula
     */
    public function validatePhoto(UploadedFile $photo): void
    {
        // Dosya boyutu kontrolü
        if ($photo->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(
                'Fotoğraf boyutu çok büyük. Maksimum ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB olabilir.'
            );
        }

        // MIME type kontrolü
        if (!in_array($photo->getMimeType(), self::ALLOWED_MIMES)) {
            throw new \InvalidArgumentException(
                'Geçersiz dosya formatı. Sadece JPEG, PNG ve WebP formatları kabul edilir.'
            );
        }

        // Gerçek bir resim dosyası mı kontrol et
        $imageInfo = @getimagesize($photo->getPathname());
        if ($imageInfo === false) {
            throw new \InvalidArgumentException('Geçersiz resim dosyası.');
        }
    }

    /**
     * POD bilgisini getir
     */
    public function getDeliveryProof(Order $order): ?array
    {
        if (!$order->hasPod()) {
            return null;
        }

        return [
            'photo_url' => $order->getPodPhotoUrl(),
            'photo_path' => $order->pod_photo_path,
            'timestamp' => $order->pod_timestamp?->format('d.m.Y H:i:s'),
            'timestamp_raw' => $order->pod_timestamp,
            'location' => $order->pod_location,
            'note' => $order->pod_note,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'delivery_address' => $order->customer_address,
        ];
    }

    /**
     * POD fotoğrafını sil
     */
    public function deletePhoto(Order $order): bool
    {
        if (!$order->hasPod()) {
            return false;
        }

        // Dosyayı sil
        Storage::disk('public')->delete($order->pod_photo_path);

        // Thumbnail varsa sil
        $thumbnailPath = $this->getThumbnailPath($order->pod_photo_path);
        if (Storage::disk('public')->exists($thumbnailPath)) {
            Storage::disk('public')->delete($thumbnailPath);
        }

        // Veritabanını güncelle
        $order->update([
            'pod_photo_path' => null,
            'pod_timestamp' => null,
            'pod_location' => null,
            'pod_note' => null,
        ]);

        return true;
    }

    /**
     * Fotoğrafı işle ve kaydet
     */
    protected function processAndSavePhoto(
        UploadedFile $photo,
        string $directory,
        string $filename
    ): string {
        $fullPath = "{$directory}/{$filename}";

        // Intervention Image yüklü mü kontrol et
        if (class_exists('Intervention\Image\Laravel\Facades\Image')) {
            // Resmi optimize et
            $image = Image::read($photo->getPathname());

            // Boyutu küçült
            if ($image->width() > self::FULL_WIDTH) {
                $image->scale(width: self::FULL_WIDTH);
            }

            // Kaliteyi ayarla ve kaydet
            Storage::disk('public')->put($fullPath, $image->toJpeg(quality: 85)->encode());

            // Thumbnail oluştur
            $thumbnail = Image::read($photo->getPathname());
            $thumbnail->scale(width: self::THUMBNAIL_WIDTH);
            $thumbnailPath = $this->getThumbnailPath($fullPath);
            Storage::disk('public')->put($thumbnailPath, $thumbnail->toJpeg(quality: 75)->encode());
        } else {
            // Intervention Image yoksa direkt kaydet
            Storage::disk('public')->putFileAs($directory, $photo, $filename);
        }

        return $fullPath;
    }

    /**
     * Dosya adı oluştur
     */
    protected function generateFilename(Order $order, UploadedFile $photo): string
    {
        $extension = $photo->getClientOriginalExtension() ?: 'jpg';
        $timestamp = now()->format('Ymd_His');
        return "pod_{$order->id}_{$timestamp}.{$extension}";
    }

    /**
     * Dizin yolunu oluştur
     */
    protected function getDirectory(Order $order): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $branchId = $order->branch_id ?? 0;
        return "pod/{$year}/{$month}/branch_{$branchId}";
    }

    /**
     * Thumbnail yolunu oluştur
     */
    protected function getThumbnailPath(string $originalPath): string
    {
        $pathInfo = pathinfo($originalPath);
        return "{$pathInfo['dirname']}/thumb_{$pathInfo['basename']}";
    }

    /**
     * Belirli bir tarih aralığındaki POD'ları getir
     */
    public function getPodsByDateRange(
        int $branchId,
        string $startDate,
        string $endDate
    ): \Illuminate\Database\Eloquent\Collection {
        return Order::where('branch_id', $branchId)
            ->whereNotNull('pod_photo_path')
            ->whereBetween('pod_timestamp', [$startDate, $endDate])
            ->orderBy('pod_timestamp', 'desc')
            ->get(['id', 'order_number', 'customer_name', 'pod_photo_path', 'pod_timestamp', 'pod_location', 'pod_note']);
    }

    /**
     * POD istatistiklerini getir
     */
    public function getStats(int $branchId, string $period = 'today'): array
    {
        $query = Order::where('branch_id', $branchId)
            ->where('status', Order::STATUS_DELIVERED);

        switch ($period) {
            case 'today':
                $query->whereDate('delivered_at', today());
                break;
            case 'week':
                $query->whereBetween('delivered_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('delivered_at', now()->month)
                    ->whereYear('delivered_at', now()->year);
                break;
        }

        $totalDelivered = $query->count();
        $withPod = (clone $query)->whereNotNull('pod_photo_path')->count();

        return [
            'total_delivered' => $totalDelivered,
            'with_pod' => $withPod,
            'without_pod' => $totalDelivered - $withPod,
            'pod_rate' => $totalDelivered > 0 ? round(($withPod / $totalDelivered) * 100, 1) : 0,
        ];
    }
}
