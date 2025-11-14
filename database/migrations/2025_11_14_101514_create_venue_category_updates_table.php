<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('squash_remote')->create('venue_category_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('venue_id');
            $table->unsignedInteger('old_category_id')->nullable();
            $table->unsignedInteger('new_category_id');
            $table->json('google_place_types')->nullable();
            $table->enum('confidence_level', ['HIGH', 'MEDIUM', 'LOW'])->default('LOW');
            $table->text('reasoning')->nullable();
            $table->enum('source', ['GOOGLE_MAPPING', 'OPENAI', 'MANUAL'])->default('MANUAL');
            $table->timestamp('created_at')->nullable();
            $table->string('created_by')->nullable();
            
            // Indexes
            $table->index('venue_id');
            $table->index('created_at');
            $table->index('confidence_level');
            $table->index('source');
            
            // Foreign key (if venues table has proper constraints)
            // $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('squash_remote')->dropIfExists('venue_category_updates');
    }
};
