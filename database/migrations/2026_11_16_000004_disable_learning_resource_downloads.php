<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('learning_resources')->update(['downloadable' => false]);
    }

    public function down(): void
    {
        // Deliberately irreversible: course resources must remain non-downloadable.
    }
};
