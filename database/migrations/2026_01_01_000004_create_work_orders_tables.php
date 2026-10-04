<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('work_no', 50)->unique();
            $table->date('request_date');
            $table->foreignId('requested_by_id')->constrained('users');
            $table->foreignId('department_id')->constrained();
            $table->foreignId('work_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sample_id', 50)->index();
            $table->foreignId('sample_type_id')->constrained();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_no', 100);
            $table->string('ar_no', 100)->nullable();
            $table->string('quantity', 100)->nullable();
            $table->date('received_date')->nullable();
            $table->date('sampling_date')->nullable();
            $table->string('storage_condition', 150)->nullable();
            $table->date('required_date');
            $table->foreignId('priority_id')->nullable()->constrained()->nullOnDelete();
            $table->string('priority', 20)->default('Normal');
            $table->string('status', 30)->default('NEW')->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index(['department_id', 'status']);
            $table->index('required_date');
        });

        Schema::create('work_order_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_type_id')->constrained();
            $table->foreignId('test_method_id')->nullable()->constrained()->nullOnDelete();
            $table->text('specification')->nullable();
            $table->string('priority', 20)->default('Normal');
            $table->decimal('estimated_duration', 5, 2)->default(2.00);
            $table->string('status', 30)->default('NEW')->index();
            $table->string('review_status', 30)->nullable();
            $table->foreignId('assigned_analyst_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('assigned_instrument_id')->nullable()->constrained('instruments')->nullOnDelete();
            $table->text('result_summary')->nullable();
            $table->timestamps();
            $table->index('test_type_id');
        });

        Schema::create('work_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('analyst_id')->constrained('employees');
            $table->foreignId('allocated_by_id')->constrained('users');
            $table->timestamp('allocated_at')->useCurrent();
            $table->foreignId('instrument_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('ACTIVE');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('allocation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('previous_analyst_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('new_analyst_id')->constrained('employees');
            $table->foreignId('allocated_by_id')->constrained('users');
            $table->timestamp('allocation_time')->useCurrent();
            $table->string('reason')->nullable();
            $table->string('status', 30)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocation_histories');
        Schema::dropIfExists('work_allocations');
        Schema::dropIfExists('work_order_tests');
        Schema::dropIfExists('work_orders');
    }
};
