<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntitiesTableMigration extends Migration
{
    public function up()
    {
        Schema::create('entity', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->integer('position', false, true);
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('entity')
                ->onDelete('set null');
        });

        Schema::create('entity_tree', function (Blueprint $table) {
            $table->increments('closure_id');

            $table->integer('ancestor', false, true);
            $table->integer('descendant', false, true);
            $table->integer('depth', false, true);

            $table->foreign('ancestor')
                ->references('id')
                ->on('entity')
                ->onDelete('cascade');

            $table->foreign('descendant')
                ->references('id')
                ->on('entity')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('entity_tree');
        Schema::dropIfExists('entity');
    }
}
