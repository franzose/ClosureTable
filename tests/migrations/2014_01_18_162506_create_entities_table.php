<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->string('title')->default('The Title');
            $table->string('excerpt')->default('');
            $table->string('body')->default('');
            $table->integer('position', false, true);
            $table->softDeletes();

            // Ensures there are no ambiguous column names in queries involving the closure table
            $table->integer('ancestor')->nullable();
            $table->integer('descendant')->nullable();
            $table->integer('depth')->nullable();

            $table->foreign('parent_id')->references('id')->on('entities')->onDelete('set null');

            $table->engine = 'InnoDB';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('entities');
    }
}
