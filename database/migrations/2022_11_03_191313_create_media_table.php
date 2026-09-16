<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable();
            $table->unsignedInteger('user_id');
            $table->string('label')->nullable();
            $table->string('description')->nullable();
            $table->string('path');
            $table->string('thumbnail')->nullable();
            $table->enum('type', ['image', 'audio', 'video', 'document']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media');
    }
}
