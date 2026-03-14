<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Assign existing integrations with NULL branch_id to the first branch
        // This prevents duplicate (platform, NULL) entries from bypassing the unique constraint
        // Since the old schema had no user_id/branch_id, integrations were global (one per platform)
        // We assign them to the first branch so the unique constraint works correctly
        //
        // PRODUCTION NOTE: Bu migration tüm mevcut entegrasyonları ilk branch'e atar.
        // Birden fazla branch varsa, production'da migration sonrası entegrasyonların
        // doğru branch'lere manuel olarak atanması gerekir (admin panelinden veya tinker ile).
        $firstBranch = DB::table('branches')->orderBy('id')->first();

        if ($firstBranch) {
            DB::table('integrations')
                ->whereNull('branch_id')
                ->update(['branch_id' => $firstBranch->id]);
        }
    }

    public function down(): void
    {
        // Set branch_id back to NULL for all integrations
        DB::table('integrations')->update(['branch_id' => null]);
    }
};
