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
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('name');
            $table->integer('age')->nullable();
            $table->string('gender')->nullable();
            $table->text('bio')->nullable();
            $table->string('occupation')->nullable();
            $table->json('traits')->nullable();
            $table->text('interests')->nullable();
            $table->string('location')->nullable();
            $table->string('dialogue_style')->nullable();
            $table->string('status')->default('active');
            $table->string('image_url')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
