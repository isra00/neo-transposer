<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        // Local databases got the column by hand before migrations existed.
        if (Schema::hasColumn('song', 'verified_at')) {
            return;
        }

        Schema::table('song', function (Blueprint $table) {
            $table->dateTime('verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('song', function (Blueprint $table) {
            $table->dropColumn('verified_at');
        });
    }
};
