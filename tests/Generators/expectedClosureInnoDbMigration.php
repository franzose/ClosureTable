<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntityTreesTable extends Migration
{
    public function up()
    {
        Schema::create('entity_tree', function(Blueprint $table) {
            $table->increments('closure_id');

            $table->integer('ancestor', false, true);
            $table->integer('descendant', false, true);
            $table->integer('depth', false, true);

            $table->foreign('ancestor')->references('id')->on('entity')->onDelete('cascade');
            $table->foreign('descendant')->references('id')->on('entity')->onDelete('cascade');

            $table->engine = 'InnoDB';
        });
    }

    public function down()
    {
        Schema::dropIfExists('entity_tree');
    }
}
