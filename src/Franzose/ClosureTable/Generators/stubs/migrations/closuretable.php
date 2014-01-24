<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class {{closure_class}} extends Migration {

    public function up()
    {
        Schema::create('{{closure_table}}', function(Blueprint $table){
            $table->increments('ctid');

            $table->integer('ancestor', false, true);
            $table->integer('descendant', false, true);
            $table->integer('depth', false, true);

            $table->foreign('ancestor')->references('id')->on('{{entity_table}}');
            $table->foreign('descendant')->references('id')->on('{{entity_table}}');
        });
    }

    public function down()
    {
        Schema::drop('{{closure_table}}');
    }
}