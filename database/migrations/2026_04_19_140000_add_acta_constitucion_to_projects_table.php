<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('acta_constitucion_path')->nullable()->after('description');
            $table->string('acta_constitucion_original_name')->nullable()->after('acta_constitucion_path');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'acta_constitucion_path',
                'acta_constitucion_original_name',
            ]);
        });
    }
};
