<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntitiesClosureTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entities_closure', function (Blueprint $table) {
            $table->increments('closure_id');
            $table->unsignedInteger('ancestor');
            $table->unsignedInteger('descendant');
            $table->unsignedInteger('depth');

            $table->foreign('ancestor')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('descendant')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');

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
        Schema::drop('entities_closure');
    }
}
