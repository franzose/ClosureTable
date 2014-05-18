<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class {{closure_class}} extends Migration {

    public function up()
    {
        Schema::table('{{closure_table}}', function(Blueprint $table){
            $table->engine = 'InnoDB';
            Schema::create('{{closure_table}}', function(Blueprint $t){
                $t->increments('ctid');

                $t->integer('ancestor', false, true);
                $t->integer('descendant', false, true);
                $t->integer('depth', false, true);

                $t->foreign('ancestor')->references('id')->on('{{entity_table}}');
                $t->foreign('descendant')->references('id')->on('{{entity_table}}');
            });
        });
    }

    public function down()
    {
        Schema::table('{{closure_table}}', function(Blueprint $table)
        {
            Schema::dropIfExists('{{closure_table}}');
        }
    }
}
