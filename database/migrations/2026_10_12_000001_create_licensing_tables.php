<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('nip', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Klucz to podpisany payload — raz wystawionego nie da się zmienić. Przedłużenie
        // albo zmiana edycji = nowy wiersz, a stary dostaje replaced_by_id.
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('domain');
            $table->string('edition', 20);
            $table->date('issued_on');
            $table->date('expires_on')->nullable();
            $table->string('note')->nullable();
            $table->text('license_key');
            $table->char('key_hash', 64)->unique();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason')->nullable();
            $table->foreignId('replaced_by_id')->nullable()->constrained('licenses')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_check_at')->nullable();
            $table->string('last_check_domain')->nullable();
            $table->timestamps();

            $table->index('domain');
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('license_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('licenses');
        Schema::dropIfExists('customers');
    }
};
