<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('unidad_medida', 20);
            $table->unsignedInteger('stock_minimo')->default(15);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('insumo_existencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')->constrained()->restrictOnDelete();
            $table->foreignId('sector_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('cantidad')->default(0);
            $table->timestamps();

            $table->unique(['insumo_id', 'sector_id']);
        });

        Schema::create('insumo_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitante_user_id')->constrained('users')->restrictOnDelete();
            $table->string('estado', 20)->default('pendiente')->index();
            $table->text('observacion')->nullable();
            $table->string('firma')->nullable();
            $table->foreignId('rechazada_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_rechazo')->nullable();
            $table->timestamps();
        });

        Schema::create('insumo_solicitud_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_solicitud_id')->constrained('insumo_solicitudes')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('cantidad_solicitada');
            $table->timestamps();

            $table->unique(['insumo_solicitud_id', 'insumo_id']);
        });

        Schema::create('insumo_importaciones', function (Blueprint $table) {
            $table->id();
            $table->string('archivo_nombre');
            $table->char('archivo_hash', 64)->unique();
            $table->date('fecha_stock')->nullable();
            $table->foreignId('imported_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('estado', 20)->default('procesando')->index();
            $table->unsignedInteger('filas_leidas')->default(0);
            $table->unsignedInteger('insumos_creados')->default(0);
            $table->unsignedInteger('saldos_actualizados')->default(0);
            $table->unsignedInteger('sin_cambios')->default(0);
            $table->json('advertencias')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('insumo_movimientos', function (Blueprint $table) {
            $table->id();
            $table->uuid('operacion_uuid')->index();
            $table->foreignId('insumo_id')->constrained()->restrictOnDelete();
            $table->foreignId('sector_id')->constrained()->restrictOnDelete();
            $table->string('tipo', 20)->index();
            $table->unsignedInteger('cantidad');
            $table->unsignedInteger('stock_anterior');
            $table->unsignedInteger('stock_posterior');
            $table->foreignId('asignado_a_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('asignado_a_funcionario_id')->nullable()->constrained('funcionarios')->nullOnDelete();
            $table->foreignId('solicitud_detalle_id')->nullable()->constrained('insumo_solicitud_detalles')->nullOnDelete();
            $table->foreignId('importacion_id')->nullable()->constrained('insumo_importaciones')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('insumo_movimientos');
        Schema::dropIfExists('insumo_importaciones');
        Schema::dropIfExists('insumo_solicitud_detalles');
        Schema::dropIfExists('insumo_solicitudes');
        Schema::dropIfExists('insumo_existencias');
        Schema::dropIfExists('insumos');
    }
};
