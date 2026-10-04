<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('analyst_id')->constrained('employees');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->foreignId('instrument_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('test_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('result_value');
            $table->string('unit')->nullable();
            $table->string('specification_reference')->nullable();
            $table->enum('result_status', ['PASS', 'FAIL', 'OOS', 'OOT', 'PENDING'])->default('PASS');
            $table->timestamp('entered_at')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_remarks')->nullable();
            $table->text('rework_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('test_result_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_parameter_id')->constrained('test_parameters');
            $table->string('parameter_name');
            $table->text('value');
            $table->text('calculated_value')->nullable();
            $table->boolean('is_pass')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('work_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_test_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->string('action_type')->nullable();
            $table->timestamps();
        });

        Schema::create('work_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_test_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('stored_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by_id')->constrained('users');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'archived', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_attachments');
        Schema::dropIfExists('work_comments');
        Schema::dropIfExists('test_result_parameters');
        Schema::dropIfExists('test_results');
    }
};
