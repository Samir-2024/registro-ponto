<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->string('cnpj', 20)->nullable();
            $table->string('razao_social');
            $table->string('status')->default('ativo');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('person_id')->references('id')->on('people')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employers');
    }
};
