<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplatePlaceholdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_placeholders', function (Blueprint $table) {
            $table->id();
            $table->string('text');
            $table->string('value');
            $table->string('model_key')->nullable();
            $table->string('model')->nullable();
            $table->string('description')->nullable();
            $table->boolean('system')->default(false);
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
        Schema::dropIfExists('template_placeholders');
    }
}
