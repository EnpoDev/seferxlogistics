<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Models\Courier;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminReportController extends Controller
{
    /**
     * Bayi bazlı rapor sayfası
     */
    public function bayiRaporlari(Request $request)
    {
        // Tarih aralığı
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : null;
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : null;

        // Bu ay ve geçen ay başlangıç/bitiş tarihleri
        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        // Sıralama
        $sortBy = $request->get('sort', 'total_ciro');
        $sortDir = $request->get('dir', 'desc');
        $allowedSorts = ['name', 'total_siparis', 'total_ciro', 'isletme_sayisi', 'kurye_sayisi', 'bu_ay_ciro', 'gecen_ay_ciro'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'total_ciro';
        }

        // Tüm bayileri al
        $bayiler = User::whereJsonContains('roles', 'bayi')
            ->with('activeSubscription.plan')
            ->get()
            ->map(function ($bayi) use ($startDate, $endDate, $thisMonthStart, $thisMonthEnd, $lastMonthStart, $lastMonthEnd) {
                // Bayi'nin işletmelerini al (user tablosunda parent_id ile bağlı)
                $isletmeUserIds = User::where('parent_id', $bayi->id)
                    ->whereJsonContains('roles', 'isletme')
                    ->pluck('id');

                // İşletmelerin branch'lerini al
                $branchIds = Branch::whereIn('user_id', $isletmeUserIds->push($bayi->id))->pluck('id');

                // Kurye sayısı
                $kuryeSayisi = Courier::where('user_id', $bayi->id)->count();

                // İşletme sayısı
                $isletmeSayisi = $isletmeUserIds->count();

                // Sipariş istatistikleri - tarih filtreli
                $orderQuery = Order::whereIn('branch_id', $branchIds);

                // Genel toplam (tarih filtresi varsa uygula)
                $filteredQuery = clone $orderQuery;
                if ($startDate && $endDate) {
                    $filteredQuery->whereBetween('created_at', [$startDate, $endDate]);
                }
                $totalSiparis = $filteredQuery->count();
                $totalCiro = $filteredQuery->sum('total');

                // Bu ay
                $buAySiparis = (clone $orderQuery)->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])->count();
                $buAyCiro = (clone $orderQuery)->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])->sum('total');

                // Geçen ay
                $gecenAySiparis = (clone $orderQuery)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
                $gecenAyCiro = (clone $orderQuery)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->sum('total');

                // Tamamlanan sipariş oranı
                $tamamlananSiparis = (clone $orderQuery)->where('status', 'delivered')->count();
                $toplamSiparisGenel = (clone $orderQuery)->count();
                $tamamlanmaOrani = $toplamSiparisGenel > 0
                    ? round(($tamamlananSiparis / $toplamSiparisGenel) * 100, 1)
                    : 0;

                return [
                    'id' => $bayi->id,
                    'name' => $bayi->name,
                    'email' => $bayi->email,
                    'phone' => $bayi->phone,
                    'created_at' => $bayi->created_at,
                    'subscription' => $bayi->activeSubscription?->plan?->name ?? 'Abonelik Yok',
                    'subscription_status' => $bayi->activeSubscription?->status ?? 'inactive',
                    'isletme_sayisi' => $isletmeSayisi,
                    'kurye_sayisi' => $kuryeSayisi,
                    'total_siparis' => $totalSiparis,
                    'total_ciro' => $totalCiro,
                    'bu_ay_siparis' => $buAySiparis,
                    'bu_ay_ciro' => $buAyCiro,
                    'gecen_ay_siparis' => $gecenAySiparis,
                    'gecen_ay_ciro' => $gecenAyCiro,
                    'tamamlanma_orani' => $tamamlanmaOrani,
                ];
            });

        // Sıralama uygula
        $bayiler = $bayiler->sortBy(function ($item) use ($sortBy) {
            return $item[$sortBy] ?? 0;
        }, SORT_REGULAR, $sortDir === 'desc');

        // Genel toplamlar
        $genelToplam = [
            'bayi_sayisi' => $bayiler->count(),
            'isletme_sayisi' => $bayiler->sum('isletme_sayisi'),
            'kurye_sayisi' => $bayiler->sum('kurye_sayisi'),
            'total_siparis' => $bayiler->sum('total_siparis'),
            'total_ciro' => $bayiler->sum('total_ciro'),
            'bu_ay_siparis' => $bayiler->sum('bu_ay_siparis'),
            'bu_ay_ciro' => $bayiler->sum('bu_ay_ciro'),
            'gecen_ay_siparis' => $bayiler->sum('gecen_ay_siparis'),
            'gecen_ay_ciro' => $bayiler->sum('gecen_ay_ciro'),
        ];

        return view('admin.raporlar.bayi-raporlari', [
            'bayiler' => $bayiler->values(),
            'genelToplam' => $genelToplam,
            'filters' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'sort' => $sortBy,
                'dir' => $sortDir,
            ],
        ]);
    }

    /**
     * CSV Export
     */
    public function bayiRaporlariExport(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : null;
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : null;

        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $bayiler = User::whereJsonContains('roles', 'bayi')->get();

        $rows = [];
        $rows[] = ['Bayi Adı', 'E-posta', 'Telefon', 'Abonelik', 'İşletme Sayısı', 'Kurye Sayısı', 'Toplam Sipariş', 'Toplam Ciro', 'Bu Ay Sipariş', 'Bu Ay Ciro', 'Geçen Ay Sipariş', 'Geçen Ay Ciro', 'Tamamlanma Oranı'];

        foreach ($bayiler as $bayi) {
            $isletmeUserIds = User::where('parent_id', $bayi->id)
                ->whereJsonContains('roles', 'isletme')
                ->pluck('id');

            $branchIds = Branch::whereIn('user_id', $isletmeUserIds->push($bayi->id))->pluck('id');
            $kuryeSayisi = Courier::where('user_id', $bayi->id)->count();
            $isletmeSayisi = $isletmeUserIds->count();

            $orderQuery = Order::whereIn('branch_id', $branchIds);

            $filteredQuery = clone $orderQuery;
            if ($startDate && $endDate) {
                $filteredQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $totalSiparis = $filteredQuery->count();
            $totalCiro = $filteredQuery->sum('total');

            $buAySiparis = (clone $orderQuery)->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])->count();
            $buAyCiro = (clone $orderQuery)->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])->sum('total');

            $gecenAySiparis = (clone $orderQuery)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
            $gecenAyCiro = (clone $orderQuery)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->sum('total');

            $tamamlananSiparis = (clone $orderQuery)->where('status', 'delivered')->count();
            $toplamSiparisGenel = (clone $orderQuery)->count();
            $tamamlanmaOrani = $toplamSiparisGenel > 0
                ? round(($tamamlananSiparis / $toplamSiparisGenel) * 100, 1)
                : 0;

            $rows[] = [
                $bayi->name,
                $bayi->email,
                $bayi->phone ?? '-',
                $bayi->activeSubscription?->plan?->name ?? 'Abonelik Yok',
                $isletmeSayisi,
                $kuryeSayisi,
                $totalSiparis,
                number_format($totalCiro, 2, ',', '.'),
                $buAySiparis,
                number_format($buAyCiro, 2, ',', '.'),
                $gecenAySiparis,
                number_format($gecenAyCiro, 2, ',', '.'),
                '%' . $tamamlanmaOrani,
            ];
        }

        $filename = 'bayi-raporlari-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($rows as $row) {
                fputcsv($file, $row, ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
