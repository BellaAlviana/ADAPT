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
        Schema::create('diagram_audits', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');
            $table->text('narrative');
            $table->string('status', 30)->default('success'); // success, needs_clarification, failed
            $table->text('clarification_message')->nullable();
            $table->json('findings')->nullable(); // category, location, evidence, rule_citation, recommendation
            $table->json('revised_diagram_data')->nullable();
            $table->longText('revised_xml_content')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagram_audits');
    }
};
