<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Integration;
use Illuminate\Console\Command;

class AssignIntegrationsToBranches extends Command
{
    protected $signature = 'data:assign-integrations
                            {--list : Only list unassigned integrations without modifying}
                            {--auto : Auto-assign all integrations to the first branch (non-interactive)}
                            {--map= : JSON map of platform=>branch_id assignments, e.g. \'{"getir":1,"trendyol":2}\'}';

    protected $description = 'Assign integrations with NULL branch_id to their correct branches (one-time production script)';

    public function handle(): int
    {
        $unassigned = Integration::whereNull('branch_id')->get();

        if ($unassigned->isEmpty()) {
            $this->info('Tüm entegrasyonlar zaten bir branch\'e atanmış. İşlem gerekmiyor.');
            return self::SUCCESS;
        }

        // Show available branches
        $branches = Branch::orderBy('id')->get(['id', 'name']);
        $this->info("\n=== Mevcut Branch'ler ===");
        $this->table(['ID', 'İsim'], $branches->map(fn($b) => [$b->id, $b->name])->toArray());

        // Show unassigned integrations
        $this->info("\n=== Atanmamış Entegrasyonlar (branch_id = NULL) ===");
        $this->table(
            ['ID', 'Platform', 'İsim', 'Durum', 'Bağlı'],
            $unassigned->map(fn($i) => [
                $i->id,
                $i->platform,
                $i->name,
                $i->status,
                $i->is_connected ? 'Evet' : 'Hayır',
            ])->toArray()
        );

        if ($this->option('list')) {
            $this->info("\n--list modu: Değişiklik yapılmadı.");
            return self::SUCCESS;
        }

        // Auto mode: assign all to first branch
        if ($this->option('auto')) {
            $firstBranch = $branches->first();
            if (!$firstBranch) {
                $this->error('Hiç branch bulunamadı. Önce branch oluşturun.');
                return self::FAILURE;
            }

            $count = Integration::whereNull('branch_id')->update(['branch_id' => $firstBranch->id]);
            $this->info("\n{$count} entegrasyon '{$firstBranch->name}' (ID: {$firstBranch->id}) branch'ine atandı.");
            return self::SUCCESS;
        }

        // Map mode: use JSON map
        if ($mapJson = $this->option('map')) {
            $map = json_decode($mapJson, true);
            if (!is_array($map)) {
                $this->error('Geçersiz JSON formatı. Örnek: \'{"getir":1,"trendyol":2}\'');
                return self::FAILURE;
            }

            $assigned = 0;
            foreach ($unassigned as $integration) {
                $branchId = $map[$integration->platform] ?? null;
                if ($branchId && $branches->contains('id', $branchId)) {
                    $integration->update(['branch_id' => $branchId]);
                    $this->line("  ✓ {$integration->platform} (ID: {$integration->id}) → Branch ID: {$branchId}");
                    $assigned++;
                } else {
                    $this->warn("  ✗ {$integration->platform} (ID: {$integration->id}) → Map'te bulunamadı veya geçersiz branch ID");
                }
            }

            $this->info("\n{$assigned}/{$unassigned->count()} entegrasyon atandı.");
            return $assigned === $unassigned->count() ? self::SUCCESS : self::FAILURE;
        }

        // Interactive mode: ask for each integration
        $this->info("\nHer entegrasyon için branch seçimi yapılacak.");
        $branchChoices = $branches->pluck('name', 'id')->toArray();

        foreach ($unassigned as $integration) {
            $branchId = $this->choice(
                "'{$integration->platform}' ({$integration->name}) hangi branch'e atansın?",
                $branchChoices,
                $branches->first()?->id
            );

            // choice returns the value (name), find the key (id)
            $selectedId = array_search($branchId, $branchChoices);
            $integration->update(['branch_id' => $selectedId]);
            $this->line("  → {$integration->platform} → Branch '{$branchId}' (ID: {$selectedId})");
        }

        $this->info("\nTüm entegrasyonlar başarıyla atandı.");
        return self::SUCCESS;
    }
}
