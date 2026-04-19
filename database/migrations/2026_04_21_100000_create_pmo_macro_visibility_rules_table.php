<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pmo_macro_visibility_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('item_key', 64)->unique();
            /** @var list<string>|null null = usar reglas por defecto del catálogo */
            $table->json('allowed_roles')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmo_macro_visibility_rules');
    }
};
