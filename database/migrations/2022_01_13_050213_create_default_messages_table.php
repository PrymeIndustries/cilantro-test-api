<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDefaultMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('default_messages', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('default')->nullable();
            $table->longtext('description')->nullable();
            $table->longtext('message')->nullable();
            $table->json('details')->default(); // new Expression('(JSON_ARRAY())')
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
        Schema::dropIfExists('default_messages');
    }
}
