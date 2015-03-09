<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class {{entity_class}} extends Migration
{
    public function up()
    {
        Schema::table('{{entity_table}}', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            Schema::create('{{entity_table}}', function(Blueprint $table)
            {
                $table->increments('id');
                $table->integer('parent_id')->unsigned()->nullable();
                $table->integer('position', false, true);
                $table->integer('real_depth', false, true);
                $table->softDeletes();

                $table->foreign('parent_id')->references('id')->on('{{entity_table}}')->onDelete('set null');
            });
        });
    }

        public function down()
    {
        Schema::table('{{entity_table}}', function(Blueprint $table)
        {
            Schema::dropIfExists('{{entity_table}}');
        });
    }
}
