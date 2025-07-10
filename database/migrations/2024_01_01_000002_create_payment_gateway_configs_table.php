<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('payment_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_name');
            $table->string('environment'); // production, test, sandbox
            $table->json('config');
            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(999);
            $table->timestamp('last_health_check')->nullable();
            $table->boolean('is_healthy')->default(true);
            $table->timestamps();

            $table->unique(['gateway_name', 'environment']);
            $table->index(['enabled', 'priority']);
            $table->index('is_healthy');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_configs');
    }
};
