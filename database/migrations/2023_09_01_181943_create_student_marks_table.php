<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentMarksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_marks', function (Blueprint $table) {
            $table->id();
            $table->integer('submission_id');
            $table->integer('teacher_id');
            $table->integer('student_id');
            $table->integer('category_id');
            $table->integer('subject_id');
            $table->float('marks_obtained');
            $table->integer('total_marks');
            $table->integer('submission_type_id');
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
        Schema::dropIfExists('student_marks');
    }
}
