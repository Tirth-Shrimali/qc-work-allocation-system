<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users');
            $table->string('action', 50);
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->text('reason')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
            $table->index(['work_order_test_id', 'created_at']);
            $table->index('reviewer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_histories');
    }
};
