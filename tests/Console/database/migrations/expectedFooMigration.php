<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFoosTableMigration extends Migration
{
    public function up()
    {
        Schema::create('foo', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->integer('position', false, true);
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('foo')
                ->onDelete('set null');

            $table->engine = 'InnoDB';
        });

        Schema::create('foo_tree', function (Blueprint $table) {
            $table->increments('closure_id');

            $table->integer('ancestor', false, true);
            $table->integer('descendant', false, true);
            $table->integer('depth', false, true);

            $table->foreign('ancestor')
                ->references('id')
                ->on('foo')
                ->onDelete('cascade');

            $table->foreign('descendant')
                ->references('id')
                ->on('foo')
                ->onDelete('cascade');

            $table->engine = 'InnoDB';
        });
    }

    public function down()
    {
        Schema::dropIfExists('foo_tree');
        Schema::dropIfExists('foo');
    }
}
