<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class {{entity_class}} extends Migration {

    public function up()
    {
        Schema::create('{{entity_table}}', function(Blueprint $table){
            $table->increments('id');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->integer('position', false, true);
            $table->integer('real_depth', false, true);
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('{{entity_table}}');
        });
    }

    public function down()
    {
        Schema::drop('{{entity_table}}');
    }
}
