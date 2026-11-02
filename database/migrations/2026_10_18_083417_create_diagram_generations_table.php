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
        Schema::create('diagram_generations', function (Blueprint $table) {
            $table->id();
            $table->text('narrative');
            $table->string('status', 30)->default('success'); // success, needs_clarification, failed
            $table->text('clarification_message')->nullable();
            $table->json('diagram_data')->nullable(); // nodes, edges, partitions/swimlanes
            $table->longText('xml_content')->nullable();
            $table->json('validation_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagram_generations');
    }
};
