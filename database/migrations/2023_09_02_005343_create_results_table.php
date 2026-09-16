<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->integer('student_id');
            $table->integer('category_id');
            $table->float('marks_obtained');
            $table->float('marks_obtained_ptg');
            $table->float('average');
            $table->integer('total_marks');
            $table->integer('class_rank');
            $table->integer('overall_rank');
            $table->boolean('promoted');
            $table->enum('decision', ['PROMOTED', 'REPEATED']);
            $table->string('remark')->nullable();
            $table->boolean('published')->default(false);
            $table->boolean('passed_examination')->default(false);
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
        Schema::dropIfExists('results');
    }
}
