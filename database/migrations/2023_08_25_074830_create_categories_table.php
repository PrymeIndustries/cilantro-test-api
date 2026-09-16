<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->integer('fees')->nullable()->default(null);
            $table->integer('report_sheet_template_id')->nullable()->default(null);
            $table->integer('class_master_teacher_id')->nullable()->default(null);
            $table->boolean('has_subcategories');
            $table->boolean('is_examination_class')->default(false);
            $table->json('cstp')->nullable()->default('[100, 100, 100]');
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
        Schema::dropIfExists('categories');
    }
}
