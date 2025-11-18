<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resource_files', function (Blueprint $table) {
            $table->renameColumn('type', 'description');
        });
    }

    public function down(): void
    {
        Schema::table('resource_files', function (Blueprint $table) {
            $table->renameColumn('description', 'type');
        });
    }
};
