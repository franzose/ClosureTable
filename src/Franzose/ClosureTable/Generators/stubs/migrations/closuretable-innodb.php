<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class {{closure_class}} extends Migration
{
    public function up()
    {
        Schema::table('{{closure_table}}', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            Schema::create('{{closure_table}}', function(Blueprint $table)
            {
                $table->increments('closure_id');

                $table->integer('ancestor', false, true);
                $table->integer('descendant', false, true);
                $table->integer('depth', false, true);

                $table->foreign('ancestor')->references('id')->on('{{entity_table}}')->onDelete('cascade');
                $table->foreign('descendant')->references('id')->on('{{entity_table}}')->onDelete('cascade');
            });
        });
    }

        public function down()
    {
        Schema::table('{{closure_table}}', function(Blueprint $table)
        {
            Schema::dropIfExists('{{closure_table}}');
        });
    }
}
