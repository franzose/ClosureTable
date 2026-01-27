<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNodesTableMigration extends Migration
{
    public function up()
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->integer('position', false, true);
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('nodes')
                ->onDelete('set null');
        });

        Schema::create('nodes_closure', function (Blueprint $table) {
            $table->increments('closure_id');

            $table->integer('ancestor', false, true);
            $table->integer('descendant', false, true);
            $table->integer('depth', false, true);

            $table->foreign('ancestor')
                ->references('id')
                ->on('nodes')
                ->onDelete('cascade');

            $table->foreign('descendant')
                ->references('id')
                ->on('nodes')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('nodes_closure');
        Schema::dropIfExists('nodes');
    }
}
