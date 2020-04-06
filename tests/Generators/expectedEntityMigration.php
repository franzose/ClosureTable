<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntitiesTable extends Migration
{
    public function up()
    {
        Schema::create('entity', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->integer('position', false, true);
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('entity')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('entity');
    }
}
