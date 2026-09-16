<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimetablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('params');
            $table->longText('value');
            $table->unsignedBigInteger('category_id')->nullable()->default(null);
            $table->unsignedBigInteger('sub_category_id')->nullable()->default(null);
            $table->boolean('is_general')->default(false);
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->unsignedBigInteger('term_id')->nullable();
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
        Schema::dropIfExists('timetables');
    }
}
