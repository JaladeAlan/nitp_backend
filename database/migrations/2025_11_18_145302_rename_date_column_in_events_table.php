<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('date', 'start_date');
            $table->date('end_date')->nullable(); // add end_date column if missing
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('start_date', 'date');
            $table->dropColumn('end_date');
        });
    }
};
