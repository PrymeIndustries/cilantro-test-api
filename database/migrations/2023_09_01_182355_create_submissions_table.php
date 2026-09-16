<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->longText('description');
            $table->longText('script')->nullable()->default(null);
            $table->integer('teacher_id');
            $table->integer('category_id');
            $table->integer('subject_id');
            $table->integer('total_marks');
            $table->enum('status', ['approved', 'pending', 'declined'])->default('pending');
            $table->boolean('approved')->default(false);
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
        Schema::dropIfExists('submissions');
    }
}
