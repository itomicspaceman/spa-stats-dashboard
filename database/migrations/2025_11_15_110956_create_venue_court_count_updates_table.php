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
        Schema::connection('squash_remote')->create('venue_court_count_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('venue_id');
            $table->unsignedInteger('old_court_count')->nullable();
            $table->unsignedInteger('new_court_count');
            $table->enum('confidence_level', ['HIGH', 'MEDIUM', 'LOW'])->default('LOW');
            $table->text('reasoning')->nullable();
            $table->string('source_url')->nullable();
            $table->enum('source_type', ['VENUE_WEBSITE', 'SOCIAL_MEDIA', 'BOOKING_PAGE', 'GOOGLE_REVIEWS', 'OTHER'])->nullable();
            $table->string('search_api_used')->nullable(); // Which API was used (google_custom_search, serpapi, tavily, openai)
            $table->json('search_results_summary')->nullable(); // Summary of search results
            $table->timestamp('created_at')->nullable();
            $table->string('created_by')->nullable();
            
            // Indexes
            $table->index('venue_id');
            $table->index('created_at');
            $table->index('confidence_level');
            $table->index('source_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('squash_remote')->dropIfExists('venue_court_count_updates');
    }
};
