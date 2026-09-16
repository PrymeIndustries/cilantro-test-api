<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubjectPeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subject_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('timetable_id');
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->string('day', 50);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('time');
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
        Schema::dropIfExists('subject_periods');
    }
}
