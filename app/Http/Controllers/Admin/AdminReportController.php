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
        // Tarih aralığı - validation ile
        try {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->start_date)->startOfDay()
                : null;
            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->end_date)->endOfDay()
                : null;
        } catch (\Exception $e) {
            return back()->with('error', 'Gecersiz tarih formati');
        }

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
        $bayiUsers = User::whereJsonContains('roles', 'bayi')
            ->with('activeSubscription.plan')
            ->get();

        $bayiIds = $bayiUsers->pluck('id')->toArray();

        // N+1 OPTIMIZE: Tum isletme user'larini tek sorguda al
        $allIsletmeUsers = User::whereIn('parent_id', $bayiIds)
            ->whereJsonContains('roles', 'isletme')
            ->get()
            ->groupBy('parent_id');

        // N+1 OPTIMIZE: Tum branch'leri tek sorguda al
        $allIsletmeUserIds = $allIsletmeUsers->flatten()->pluck('id')->toArray();
        $allUserIds = array_merge($bayiIds, $allIsletmeUserIds);
        $allBranches = Branch::whereIn('user_id', $allUserIds)->get()->groupBy('user_id');

        // N+1 OPTIMIZE: Tum kuryeleri tek sorguda al
        $allCouriers = Courier::whereIn('user_id', $bayiIds)
            ->select('user_id', DB::raw('count(*) as count'))
            ->groupBy('user_id')
            ->pluck('count', 'user_id');

        // N+1 OPTIMIZE: Siparis istatistiklerini toplu hesapla
        $allBranchIds = $allBranches->flatten()->pluck('id')->toArray();

        // Branch -> bayi eslestirmesi olustur
        $branchToBayi = [];
        foreach ($bayiUsers as $bayi) {
            $isletmeUsers = $allIsletmeUsers->get($bayi->id, collect());
            $isletmeUserIds = $isletmeUsers->pluck('id')->toArray();
            $userIds = array_merge([$bayi->id], $isletmeUserIds);

            foreach ($userIds as $userId) {
                $branches = $allBranches->get($userId, collect());
                foreach ($branches as $branch) {
                    $branchToBayi[$branch->id] = $bayi->id;
                }
            }
        }

        // Siparis istatistiklerini tek sorguda al
        $orderStatsQuery = Order::whereIn('branch_id', $allBranchIds)
            ->select(
                'branch_id',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(total) as total_sum'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_count'),
                DB::raw('SUM(CASE WHEN created_at BETWEEN "' . $thisMonthStart->toDateTimeString() . '" AND "' . $thisMonthEnd->toDateTimeString() . '" THEN 1 ELSE 0 END) as this_month_count'),
                DB::raw('SUM(CASE WHEN created_at BETWEEN "' . $thisMonthStart->toDateTimeString() . '" AND "' . $thisMonthEnd->toDateTimeString() . '" THEN total ELSE 0 END) as this_month_sum'),
                DB::raw('SUM(CASE WHEN created_at BETWEEN "' . $lastMonthStart->toDateTimeString() . '" AND "' . $lastMonthEnd->toDateTimeString() . '" THEN 1 ELSE 0 END) as last_month_count'),
                DB::raw('SUM(CASE WHEN created_at BETWEEN "' . $lastMonthStart->toDateTimeString() . '" AND "' . $lastMonthEnd->toDateTimeString() . '" THEN total ELSE 0 END) as last_month_sum')
            );

        if ($startDate && $endDate) {
            $orderStatsQuery->addSelect(
                DB::raw('SUM(CASE WHEN created_at BETWEEN "' . $startDate->toDateTimeString() . '" AND "' . $endDate->toDateTimeString() . '" THEN 1 ELSE 0 END) as filtered_count'),
                DB::raw('SUM(CASE WHEN created_at BETWEEN "' . $startDate->toDateTimeString() . '" AND "' . $endDate->toDateTimeString() . '" THEN total ELSE 0 END) as filtered_sum')
            );
        }

        $orderStats = $orderStatsQuery->groupBy('branch_id')->get()->keyBy('branch_id');

        // Bayi bazli istatistikleri hesapla
        $bayiler = $bayiUsers->map(function ($bayi) use ($allIsletmeUsers, $allBranches, $allCouriers, $orderStats, $branchToBayi, $startDate, $endDate) {
            $isletmeUsers = $allIsletmeUsers->get($bayi->id, collect());
            $isletmeSayisi = $isletmeUsers->count();
            $kuryeSayisi = $allCouriers->get($bayi->id, 0);

            // Bu bayi'nin branch'lerini bul
            $isletmeUserIds = $isletmeUsers->pluck('id')->toArray();
            $userIds = array_merge([$bayi->id], $isletmeUserIds);

            $bayiBranchIds = [];
            foreach ($userIds as $userId) {
                $branches = $allBranches->get($userId, collect());
                $bayiBranchIds = array_merge($bayiBranchIds, $branches->pluck('id')->toArray());
            }

            // Siparis istatistiklerini topla
            $totalSiparis = 0;
            $totalCiro = 0;
            $buAySiparis = 0;
            $buAyCiro = 0;
            $gecenAySiparis = 0;
            $gecenAyCiro = 0;
            $tamamlananSiparis = 0;
            $toplamSiparisGenel = 0;

            foreach ($bayiBranchIds as $branchId) {
                $stats = $orderStats->get($branchId);
                if ($stats) {
                    if ($startDate && $endDate) {
                        $totalSiparis += $stats->filtered_count ?? 0;
                        $totalCiro += $stats->filtered_sum ?? 0;
                    } else {
                        $totalSiparis += $stats->total_count ?? 0;
                        $totalCiro += $stats->total_sum ?? 0;
                    }
                    $buAySiparis += $stats->this_month_count ?? 0;
                    $buAyCiro += $stats->this_month_sum ?? 0;
                    $gecenAySiparis += $stats->last_month_count ?? 0;
                    $gecenAyCiro += $stats->last_month_sum ?? 0;
                    $tamamlananSiparis += $stats->delivered_count ?? 0;
                    $toplamSiparisGenel += $stats->total_count ?? 0;
                }
            }

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
        try {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->start_date)->startOfDay()
                : null;
            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->end_date)->endOfDay()
                : null;
        } catch (\Exception $e) {
            return back()->with('error', 'Gecersiz tarih formati');
        }

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
