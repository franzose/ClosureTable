<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntitiesClosureTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('entities_closure', function(Blueprint $table)
		{
            $table->increments('ctid');
            $table->integer('ancestor');
            $table->integer('descendant');
            $table->integer('depth');

            $table->foreign('ancestor')->references('id')->on('entities');
            $table->foreign('descendant')->references('id')->on('entities');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('entities_closure');
	}

}
