<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KVKK/GDPR Uyumluluk Tablolari
 *
 * Bu migration asagidaki tablolari olusturur:
 * 1. user_consents: Kullanici rizalari
 * 2. data_deletion_logs: Veri silme kayitlari
 * 3. data_access_logs: Veri erisim kayitlari
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Riza Yonetimi Tablosu
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('courier_id')->nullable();
            $table->string('consent_type', 50); // marketing, newsletter, data_processing, location_tracking
            $table->boolean('is_granted')->default(false);
            $table->text('consent_text')->nullable(); // Gosterilen metin
            $table->string('consent_version', 20)->default('1.0'); // Politika versiyonu
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('withdrawal_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'consent_type']);
            $table->index(['courier_id', 'consent_type']);
            $table->index('consent_type');
        });

        // 2. Veri Silme Kayitlari (KVKK Madde 7)
        Schema::create('data_deletion_logs', function (Blueprint $table) {
            $table->id();
            $table->string('deletable_type'); // Model sinifi
            $table->unsignedBigInteger('deletable_id');
            $table->string('reason'); // user_request, retention_policy, legal_requirement
            $table->timestamp('anonymized_at');
            $table->unsignedBigInteger('requested_by')->nullable(); // Islemi yapan kullanici
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable(); // Ek bilgiler
            $table->timestamps();

            $table->index(['deletable_type', 'deletable_id']);
            $table->index('anonymized_at');
        });

        // 3. Veri Erisim Kayitlari (KVKK Madde 12)
        Schema::create('data_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type', 20); // user, courier, admin, system
            $table->string('action', 50); // view, export, update, delete
            $table->string('resource_type'); // Model sinifi
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('request_data')->nullable(); // Maskelenmis istek verisi
            $table->string('status', 20)->default('success'); // success, denied, failed
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
            $table->index('created_at');
        });

        // 4. Gizlilik Politikasi Kabul Kayitlari
        Schema::create('privacy_policy_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('courier_id')->nullable();
            $table->string('policy_version', 20);
            $table->string('policy_type', 30)->default('privacy'); // privacy, terms, cookies
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->index(['user_id', 'policy_type']);
            $table->index('policy_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_policy_acceptances');
        Schema::dropIfExists('data_access_logs');
        Schema::dropIfExists('data_deletion_logs');
        Schema::dropIfExists('user_consents');
    }
};
