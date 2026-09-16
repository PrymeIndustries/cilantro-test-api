<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->date('date_of_birth')->nullable()->default(null);
            $table->string('matricule')->nullable()->default(null);
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->enum('gender', ['M', 'F']);
            $table->string('address')->nullable();
            $table->integer('category_id');
            $table->integer('sub_category_id')->nullable()->default(null);
            $table->integer('guardian_id')->nullable()->default(null);
            $table->string('guardian_name')->nullable()->default(null);
            $table->float('fees')->nullable()->default(null);
            $table->float('fees_amount_paid')->default(0);
            $table->enum('fees_status', ['unpaid', 'incomplete', 'complete'])->default('unpaid');
            $table->json('fees_history')->nullable()->default(null);
            $table->boolean('paid_fees')->default(false);
            $table->boolean('present')->default(true);
            $table->longText('photo')->default('/images/student_avatar.png');
            $table->longText('photo_thumbnail')->default('/images/student_avatar.png');
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
        Schema::dropIfExists('students');
    }
}
