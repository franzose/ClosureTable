<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBarsTableMigration extends Migration
{
    public function up()
    {
        Schema::create('bars', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->integer('position', false, true);
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('bars')
                ->onDelete('set null');

        });

        Schema::create('bar_tree', function (Blueprint $table) {
            $table->increments('closure_id');

            $table->integer('ancestor', false, true);
            $table->integer('descendant', false, true);
            $table->integer('depth', false, true);

            $table->foreign('ancestor')
                ->references('id')
                ->on('bars')
                ->onDelete('cascade');

            $table->foreign('descendant')
                ->references('id')
                ->on('bars')
                ->onDelete('cascade');

        });
    }

    public function down()
    {
        Schema::dropIfExists('bar_tree');
        Schema::dropIfExists('bars');
    }
}
