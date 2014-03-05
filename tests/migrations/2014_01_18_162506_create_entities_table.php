<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntitiesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('entities', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer('parent_id')->nullable();
            $table->string('title')->default('The Title');
            $table->text('excerpt')->default('The excerpt');
            $table->longText('body')->default('The content');
            $table->integer('position', false, true);
            $table->integer('depth', false, true);
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('entities');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('entities');
	}

}
