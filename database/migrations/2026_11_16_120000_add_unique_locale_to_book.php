<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every locale has exactly one book.
 */
return new class() extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE book ADD UNIQUE KEY locale (locale)');
    }
};
