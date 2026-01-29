<?php

namespace App\Services;

use App\Models\Courier;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ShiftManagementService
{
    // Varsayılan vardiya şablonları
    private const SHIFT_TEMPLATES = [
        'sabah' => ['start' => '08:00', 'end' => '16:00', 'name' => 'Sabah Vardiyası'],
        'ogle' => ['start' => '12:00', 'end' => '20:00', 'name' => 'Öğle Vardiyası'],
        'aksam' => ['start' => '16:00', 'end' => '24:00', 'name' => 'Akşam Vardiyası'],
        'gece' => ['start' => '20:00', 'end' => '04:00', 'name' => 'Gece Vardiyası'],
        'tam_gun' => ['start' => '10:00', 'end' => '22:00', 'name' => 'Tam Gün'],
        'hafta_sonu' => ['start' => '11:00', 'end' => '23:00', 'name' => 'Hafta Sonu'],
    ];

    /**
     * Kurye vardiya durumunu kontrol et
     */
    public function checkCourierShiftStatus(Courier $courier): array
    {
        $isOnShift = $courier->isOnShift();
        $shifts = $this->parseShifts($courier->shifts);
        $today = strtolower(now()->format('l'));
        $currentShift = $shifts[$today] ?? null;

        $nextShift = null;
        $minutesUntilShift = null;

        if (!$isOnShift && $currentShift) {
            $shiftStart = Carbon::parse($currentShift['start']);
            if (now()->lt($shiftStart)) {
                $nextShift = $currentShift;
                $minutesUntilShift = now()->diffInMinutes($shiftStart);
            }
        }

        // Bugünün kalan vardiya süresi
        $remainingMinutes = null;
        if ($isOnShift && $currentShift) {
            $shiftEnd = Carbon::parse($currentShift['end']);
            if ($shiftEnd->lt(Carbon::parse($currentShift['start']))) {
                $shiftEnd->addDay();
            }
            $remainingMinutes = max(0, now()->diffInMinutes($shiftEnd, false));
        }

        return [
            'is_on_shift' => $isOnShift,
            'current_shift' => $isOnShift ? $currentShift : null,
            'next_shift' => $nextShift,
            'minutes_until_shift' => $minutesUntilShift,
            'remaining_minutes' => $remainingMinutes,
            'weekly_shifts' => $shifts,
        ];
    }

    /**
     * Vardiya şablonu uygula
     */
    public function applyShiftTemplate(Courier $courier, string $templateKey, ?array $days = null): bool
    {
        $template = self::SHIFT_TEMPLATES[$templateKey] ?? null;

        if (!$template) {
            return false;
        }

        $shifts = $this->parseShifts($courier->shifts);
        $daysToApply = $days ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($daysToApply as $day) {
            $shifts[$day] = [
                'enabled' => true,
                'start' => $template['start'],
                'end' => $template['end'],
            ];
        }

        $courier->update(['shifts' => json_encode($shifts)]);

        return true;
    }

    /**
     * Haftalık vardiya planı oluştur
     */
    public function createWeeklySchedule(array $schedule): array
    {
        $formattedSchedule = [];

        foreach ($schedule as $day => $shift) {
            if (!empty($shift['enabled'])) {
                $formattedSchedule[$day] = [
                    'enabled' => true,
                    'start' => $shift['start'] ?? '09:00',
                    'end' => $shift['end'] ?? '18:00',
                ];
            } else {
                $formattedSchedule[$day] = ['enabled' => false];
            }
        }

        return $formattedSchedule;
    }

    /**
     * Vardiya çakışma kontrolü
     */
    public function checkShiftConflicts(Courier $courier, string $day, string $start, string $end): array
    {
        $conflicts = [];

        // Aynı gün içinde çakışan vardiyalar
        $shifts = $this->parseShifts($courier->shifts);
        $existingShift = $shifts[$day] ?? null;

        if ($existingShift && $existingShift['enabled']) {
            $existingStart = Carbon::parse($existingShift['start']);
            $existingEnd = Carbon::parse($existingShift['end']);
            $newStart = Carbon::parse($start);
            $newEnd = Carbon::parse($end);

            if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                $conflicts[] = [
                    'type' => 'overlap',
                    'message' => "Bu gün için zaten {$existingShift['start']} - {$existingShift['end']} vardiyası mevcut.",
                ];
            }
        }

        // Minimum dinlenme süresi kontrolü (8 saat)
        $prevDay = Carbon::parse($day)->subDay()->format('l');
        $prevDayShift = $shifts[strtolower($prevDay)] ?? null;

        if ($prevDayShift && $prevDayShift['enabled']) {
            $prevEnd = Carbon::parse($prevDayShift['end']);
            $newStart = Carbon::parse($start);

            // Önceki gün gece geçiyorsa
            if ($prevEnd->lt(Carbon::parse($prevDayShift['start']))) {
                $prevEnd->addDay();
            }

            $restHours = $prevEnd->diffInHours($newStart);
            if ($restHours < 8 && $restHours > 0) {
                $conflicts[] = [
                    'type' => 'rest_time',
                    'message' => "Önceki vardiya ile arasında en az 8 saat dinlenme süresi olmalı. Mevcut: {$restHours} saat.",
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Vardiya istatistikleri
     */
    public function getShiftStatistics(Carbon $startDate, Carbon $endDate, ?int $courierId = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->whereNotNull('courier_id');

        if ($courierId) {
            $query->where('courier_id', $courierId);
        }

        $orders = $query->get();

        // Saatlik dağılım
        $hourlyDistribution = $orders->groupBy(function ($order) {
            return $order->created_at->format('H');
        })->map->count()->sortKeys();

        // En yoğun saatler
        $peakHours = $hourlyDistribution->sortDesc()->take(3)->keys()->toArray();

        // Kurye bazlı vardiya verimliliği
        $courierEfficiency = [];
        if (!$courierId) {
            $courierEfficiency = $orders->groupBy('courier_id')->map(function ($courierOrders) {
                $courier = $courierOrders->first()->courier;
                $totalHours = $this->calculateTotalShiftHours($courier);

                return [
                    'courier_name' => $courier?->name ?? 'Bilinmeyen',
                    'deliveries' => $courierOrders->count(),
                    'shift_hours' => $totalHours,
                    'deliveries_per_hour' => $totalHours > 0
                        ? round($courierOrders->count() / $totalHours, 2)
                        : 0,
                ];
            })->sortByDesc('deliveries_per_hour')->values()->toArray();
        }

        return [
            'total_orders' => $orders->count(),
            'hourly_distribution' => $hourlyDistribution->toArray(),
            'peak_hours' => $peakHours,
            'courier_efficiency' => $courierEfficiency,
        ];
    }

    /**
     * Otomatik vardiya önerisi
     */
    public function suggestOptimalShifts(int $courierId): array
    {
        $courier = Courier::find($courierId);
        if (!$courier) {
            return [];
        }

        // Son 30 günün sipariş verilerine göre öneri
        $orders = Order::where('courier_id', $courierId)
            ->where('status', 'delivered')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        // En aktif saatleri bul
        $hourlyActivity = $orders->groupBy(function ($order) {
            return $order->created_at->format('H');
        })->map->count();

        $sortedHours = $hourlyActivity->sortDesc();
        $peakStart = $sortedHours->keys()->first() ?? 10;
        $peakEnd = min(23, $peakStart + 8);

        // En aktif günleri bul
        $dailyActivity = $orders->groupBy(function ($order) {
            return strtolower($order->created_at->format('l'));
        })->map->count()->sortDesc();

        $activeDays = $dailyActivity->keys()->take(5)->toArray();

        return [
            'suggested_start' => sprintf('%02d:00', $peakStart),
            'suggested_end' => sprintf('%02d:00', $peakEnd),
            'suggested_days' => $activeDays,
            'peak_hours' => $sortedHours->take(3)->keys()->toArray(),
            'recommendation' => "Verilere göre en verimli çalışma saatleriniz {$peakStart}:00 - {$peakEnd}:00 arası.",
        ];
    }

    /**
     * Aktif kurye sayısını saate göre getir
     */
    public function getActiveCouriersByHour(): array
    {
        $couriers = Courier::whereNotNull('shifts')->get();
        $hourlyCount = array_fill(0, 24, 0);

        foreach ($couriers as $courier) {
            $shifts = $this->parseShifts($courier->shifts);
            $today = strtolower(now()->format('l'));
            $shift = $shifts[$today] ?? null;

            if ($shift && ($shift['enabled'] ?? false)) {
                $start = (int) explode(':', $shift['start'])[0];
                $end = (int) explode(':', $shift['end'])[0];

                if ($end < $start) {
                    // Gece geçen vardiya
                    for ($h = $start; $h < 24; $h++) {
                        $hourlyCount[$h]++;
                    }
                    for ($h = 0; $h <= $end; $h++) {
                        $hourlyCount[$h]++;
                    }
                } else {
                    for ($h = $start; $h < $end; $h++) {
                        $hourlyCount[$h]++;
                    }
                }
            }
        }

        return array_map(function ($hour, $count) {
            return ['hour' => sprintf('%02d:00', $hour), 'count' => $count];
        }, array_keys($hourlyCount), $hourlyCount);
    }

    /**
     * Vardiya şablonlarını getir
     */
    public function getShiftTemplates(): array
    {
        return self::SHIFT_TEMPLATES;
    }

    /**
     * Shifts JSON'ını parse et
     */
    private function parseShifts(?string $shifts): array
    {
        if (!$shifts) {
            return [];
        }

        $parsed = json_decode($shifts, true);
        return is_array($parsed) ? $parsed : [];
    }

    /**
     * Toplam haftalık vardiya saatini hesapla
     */
    private function calculateTotalShiftHours(Courier $courier): float
    {
        $shifts = $this->parseShifts($courier->shifts);
        $totalMinutes = 0;

        foreach ($shifts as $day => $shift) {
            if ($shift['enabled'] ?? false) {
                $start = Carbon::parse($shift['start']);
                $end = Carbon::parse($shift['end']);

                if ($end->lt($start)) {
                    $end->addDay();
                }

                $totalMinutes += $start->diffInMinutes($end);
            }
        }

        return round($totalMinutes / 60, 1);
    }
}
