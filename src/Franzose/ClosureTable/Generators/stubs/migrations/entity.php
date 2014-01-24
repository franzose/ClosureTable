<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class {{entity_class}} extends Migration {

    public function up()
    {
        Schema::create('{{entity_table}}', function(Blueprint $table){
            $table->increments('id');
        });
    }

    public function down()
    {
        Schema::drop('{{entity_table}}');
    }
}