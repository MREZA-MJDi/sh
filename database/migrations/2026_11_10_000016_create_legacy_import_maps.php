<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_import_maps', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 40);
            $table->string('entity', 40);
            $table->string('legacy_id', 100);
            $table->unsignedBigInteger('target_id');
            $table->char('payload_hash', 64)->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'entity', 'legacy_id'], 'legacy_import_source_entity_id_unique');
            $table->unique(['source', 'entity', 'target_id'], 'legacy_import_source_entity_target_unique');
            $table->index(['entity', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_import_maps');
    }
};
