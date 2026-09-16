<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuardiansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('telephone');
            $table->string('telephone_2')->nullable();
            $table->enum('gender', ['M', 'F']);
            $table->string('address')->nullable();
            $table->string('occupation')->nullable();
            $table->longText('photo')->default('/images/guardian_avatar.png');
            $table->longText('photo_thumbnail')->default('/images/guardian_avatar.png');
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
        Schema::dropIfExists('guardians');
    }
}
