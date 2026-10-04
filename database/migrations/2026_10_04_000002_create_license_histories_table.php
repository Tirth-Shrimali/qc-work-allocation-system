<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_histories', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // activated, extended, expiry_changed, suspended, reactivated, policy_changed
            $table->date('previous_activation')->nullable();
            $table->date('new_activation')->nullable();
            $table->date('previous_expiry')->nullable();
            $table->date('new_expiry')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_histories');
    }
};
