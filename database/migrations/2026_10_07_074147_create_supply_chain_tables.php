<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('distributors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('account_number')->unique();
            $table->decimal('monthly_target', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->timestamps();
            $table->unique(['distributor_id', 'code']);
            $table->index(['distributor_id', 'name']);
        });

        Schema::create('forecast_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('week_number');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('low_band');
            $table->unsignedInteger('expected_band');
            $table->unsignedInteger('high_band');
            $table->timestamps();
            $table->unique(['location_id', 'year', 'week_number']);
        });

        Schema::create('demand_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('week_number');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('requested_qty');
            $table->unsignedInteger('approved_qty')->nullable();
            $table->unsignedInteger('dispatched_qty')->nullable();
            $table->unsignedInteger('confirmed_qty')->nullable();
            $table->unsignedInteger('sales_qty')->nullable();
            $table->enum('status', ['pending', 'auto_approved', 'flagged', 'adjusted', 'delivered', 'completed'])
                ->default('pending');
            $table->boolean('company_short_supply')->default(false);
            $table->text('flag_reason')->nullable();
            $table->string('confirmation_code', 6)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'year', 'week_number']);
            $table->index(['distributor_id', 'year', 'week_number']);
            $table->index(['location_id', 'year', 'week_number']);
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demand_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('description');
            $table->enum('status', ['open', 'resolved'])->default('open')->index();
            $table->timestamps();
        });

        Schema::create('performance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('accuracy_score', 5, 2)->default(0);
            $table->decimal('target_score', 5, 2)->default(0);
            $table->decimal('timeliness_score', 5, 2)->default(0);
            $table->decimal('payment_score', 5, 2)->default(0);
            $table->decimal('total_score', 5, 2)->default(0);
            $table->uuid('verification_code')->unique();
            $table->timestamps();
            $table->unique(['distributor_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_records');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('demand_requests');
        Schema::dropIfExists('forecast_bands');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('distributors');
    }
};
