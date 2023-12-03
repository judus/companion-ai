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
            $table->integer('happiness')->default(5);
            $table->integer('interest')->default(5);
            $table->integer('sadness')->default(2);
            $table->integer('frustration')->default(3);
            $table->integer('fear')->default(2);
            $table->integer('surprise')->default(4);
            $table->integer('trust')->default(4);
            $table->integer('romantic_attachment')->default(2);
            $table->integer('confidence')->default(5);
            $table->integer('loneliness')->default(4);
            $table->integer('confusion')->default(3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn([
                'happiness',
                'interest',
                'sadness',
                'frustration',
                'fear',
                'surprise',
                'trust',
                'romantic_attachment',
                'confidence',
                'loneliness',
                'confusion',
            ]);
        });
    }
};
