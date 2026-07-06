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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('fever_provider');
            $table->string('external_base_plan_id');    //event id
            $table->string('external_plan_id');         //session id
            $table->string('title');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_base_plan_id', 'external_plan_id']);

            $table->index('external_base_plan_id');
            $table->index('starts_at');
            $table->index('ends_at');
            $table->index(['starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
