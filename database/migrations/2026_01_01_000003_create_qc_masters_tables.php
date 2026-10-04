<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique();
            $table->string('product_name');
            $table->string('generic_name')->nullable();
            $table->string('product_type')->default('Finished Product');
            $table->string('dosage_form')->nullable();
            $table->string('strength')->nullable();
            $table->string('manufacturer')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('material_code')->unique();
            $table->string('material_name');
            $table->string('material_type')->nullable();
            $table->string('material_category')->nullable();
            $table->text('specification')->nullable();
            $table->string('storage_condition')->nullable();
            $table->string('supplier')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('sample_types', function (Blueprint $table) {
            $table->id();
            $table->string('sample_type_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('work_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('priorities', function (Blueprint $table) {
            $table->id();
            $table->string('priority_code')->unique();
            $table->string('name');
            $table->integer('level')->default(1);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('status_code')->unique();
            $table->string('name');
            $table->string('category')->default('workflow');
            $table->string('color_class')->default('primary');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('skill_code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('instrument_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('instruments', function (Blueprint $table) {
            $table->id();
            $table->string('instrument_id_code')->unique();
            $table->string('name');
            $table->foreignId('instrument_type_id')->constrained();
            $table->string('instrument_code')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('availability', ['Available', 'Busy', 'Maintenance'])->default('Available');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('test_types', function (Blueprint $table) {
            $table->id();
            $table->string('test_code')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('default_unit')->nullable();
            $table->string('default_method')->nullable();
            $table->foreignId('required_skill_id')->nullable()->constrained('skills')->nullOnDelete();
            $table->decimal('estimated_duration_hours', 5, 2)->default(2.00);
            $table->string('priority')->default('Normal');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('test_methods', function (Blueprint $table) {
            $table->id();
            $table->string('method_code')->unique();
            $table->string('name');
            $table->foreignId('test_type_id')->constrained()->cascadeOnDelete();
            $table->text('method_description')->nullable();
            $table->string('version')->default('1.0');
            $table->date('effective_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('specifications', function (Blueprint $table) {
            $table->id();
            $table->string('specification_code')->unique();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('test_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('specification_value');
            $table->string('unit')->nullable();
            $table->date('effective_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->enum('skill_level', ['Beginner', 'Intermediate', 'Advanced'])->default('Intermediate');
            $table->enum('authorization_status', ['active', 'inactive'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'skill_id']);
        });

        Schema::create('test_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_type_id')->constrained()->cascadeOnDelete();
            $table->string('parameter_name');
            $table->enum('field_type', ['text', 'number', 'decimal', 'date', 'boolean', 'select', 'calculation'])->default('decimal');
            $table->string('unit')->nullable();
            $table->boolean('is_required')->default(true);
            $table->integer('sequence')->default(1);
            $table->boolean('calculation_required')->default(false);
            $table->string('formula')->nullable();
            $table->boolean('specification_required')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_parameters');
        Schema::dropIfExists('employee_skills');
        Schema::dropIfExists('specifications');
        Schema::dropIfExists('test_methods');
        Schema::dropIfExists('test_types');
        Schema::dropIfExists('instruments');
        Schema::dropIfExists('instrument_types');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('priorities');
        Schema::dropIfExists('work_categories');
        Schema::dropIfExists('sample_types');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('products');
    }
};
