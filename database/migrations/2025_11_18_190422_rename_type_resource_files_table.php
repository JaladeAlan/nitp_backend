<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resource_files', function (Blueprint $table) {
            if (Schema::hasColumn('resource_files', 'type')) {
                $table->renameColumn('type', 'description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resource_files', function (Blueprint $table) {
            if (Schema::hasColumn('resource_files', 'description')) {
                $table->renameColumn('description', 'type');
            }
        });
    }

};
