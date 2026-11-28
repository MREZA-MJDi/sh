<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name', 75)->nullable();
            $table->string('last_name', 75)->nullable();
            $table->string('national_id', 10)->nullable()->unique();
            $table->string('father_name', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('province', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('school_name', 180)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('postal_code', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['national_id']);
            $table->dropColumn([
                'first_name', 'last_name', 'national_id', 'father_name', 'birth_date',
                'province', 'city', 'school_name', 'address', 'postal_code',
            ]);
        });
    }
};
