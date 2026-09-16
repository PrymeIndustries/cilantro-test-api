<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeachersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('matricule')->nullable()->default(null);
            $table->string('email')->unique();
            $table->string('telephone');
            $table->enum('gender', ['M', 'F']);
            $table->string('address')->nullable();
            $table->longText('photo')->default('/images/teacher_avatar.png');
            $table->longText('photo_thumbnail')->default('/images/teacher_avatar.png');
            $table->boolean('active')->default(true);
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
        Schema::dropIfExists('teachers');
    }
}
