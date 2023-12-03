<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('characters', function (Blueprint $table) {
            // Change the 'traits' column from JSON to text
            $table->text('traits')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('characters', function (Blueprint $table) {
            // Revert back to JSON if needed
            $table->json('traits')->nullable()->change();
        });
    }
};
