<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Courier;
use App\Models\User;
use Illuminate\Console\Command;

class AssignOrphanedData extends Command
{
    protected $signature = 'data:assign-orphaned
                            {--user= : User ID to assign orphaned data to}
                            {--list : Only list orphaned data without assigning}
                            {--couriers : Only process couriers}
                            {--branches : Only process branches}';

    protected $description = 'Assign orphaned couriers and branches to a specific bayi user';

    public function handle(): int
    {
        $listOnly = $this->option('list');
        $userId = $this->option('user');
        $couriersOnly = $this->option('couriers');
        $branchesOnly = $this->option('branches');

        // Show available bayis
        $this->info("\n=== Mevcut Bayi Kullanicilari ===");
        $bayis = User::where('role', 'bayi')->get(['id', 'name', 'email']);

        if ($bayis->isEmpty()) {
            $this->warn('Sistemde bayi kullanicisi bulunamadi.');
            return 1;
        }

        $this->table(['ID', 'Ad', 'E-posta'], $bayis->map(fn($u) => [$u->id, $u->name, $u->email])->toArray());

        // Count orphaned data
        $orphanedCouriers = Courier::whereNull('user_id')->get();
        $orphanedBranches = Branch::whereNull('user_id')->get();

        $this->info("\n=== Sahipsiz Veriler ===");
        $this->line("Sahipsiz Kuryeler: {$orphanedCouriers->count()}");
        $this->line("Sahipsiz Isletmeler: {$orphanedBranches->count()}");

        if ($orphanedCouriers->isEmpty() && $orphanedBranches->isEmpty()) {
            $this->info("\nTum veriler zaten bir kullaniciya atanmis.");
            return 0;
        }

        // List details if requested
        if ($listOnly || !$userId) {
            if ($orphanedCouriers->isNotEmpty() && !$branchesOnly) {
                $this->info("\n--- Sahipsiz Kuryeler ---");
                $this->table(
                    ['ID', 'Ad', 'Telefon', 'Durum'],
                    $orphanedCouriers->map(fn($c) => [$c->id, $c->name, $c->phone, $c->status])->toArray()
                );
            }

            if ($orphanedBranches->isNotEmpty() && !$couriersOnly) {
                $this->info("\n--- Sahipsiz Isletmeler ---");
                $this->table(
                    ['ID', 'Ad', 'Telefon', 'Ana mi?'],
                    $orphanedBranches->map(fn($b) => [$b->id, $b->name, $b->phone, $b->is_main ? 'Evet' : 'Hayir'])->toArray()
                );
            }

            if (!$userId) {
                $this->warn("\nKullanim: php artisan data:assign-orphaned --user=<USER_ID>");
                $this->line("Ornek: php artisan data:assign-orphaned --user=1");
                return 0;
            }
        }

        // Validate user
        $user = User::find($userId);
        if (!$user) {
            $this->error("Kullanici bulunamadi: {$userId}");
            return 1;
        }

        if ($user->role !== 'bayi') {
            $this->warn("Uyari: Kullanici '{$user->name}' bayi degil (rol: {$user->role})");
            if (!$this->confirm('Devam etmek istiyor musunuz?')) {
                return 0;
            }
        }

        // Assign data
        $this->info("\nVeriler '{$user->name}' kullanicisina ataniyor...");

        if (!$branchesOnly && $orphanedCouriers->isNotEmpty()) {
            $count = Courier::whereNull('user_id')->update(['user_id' => $userId]);
            $this->info("- {$count} kurye atandi.");
        }

        if (!$couriersOnly && $orphanedBranches->isNotEmpty()) {
            $count = Branch::whereNull('user_id')->update(['user_id' => $userId]);
            $this->info("- {$count} isletme atandi.");
        }

        $this->info("\nIslem tamamlandi!");
        return 0;
    }
}
