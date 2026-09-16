<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisciplineRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discipline_records', function (Blueprint $table) {
            $table->id();
            $table->longText('note');
            $table->integer('hours_of_punishment');
            $table->integer('number_of_warnings');
            $table->integer('days_of_suspension');
            $table->integer('hours_of_absences');
            $table->integer('student_id');
            $table->integer('attendance_id')->nullable()->default(null);
            $table->integer('term_id');
            $table->integer('academic_year_id');
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
        Schema::dropIfExists('discipline_records');
    }
}
