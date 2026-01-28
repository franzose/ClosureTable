<?php
declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntitiesClosureTable extends Migration
{
    public function up(): void
    {
        Schema::create('entities_closure', static function (Blueprint $table) {
            $table->increments('closure_id');
            $table->unsignedInteger('ancestor');
            $table->unsignedInteger('descendant');
            $table->unsignedInteger('depth');

            $table->foreign('ancestor')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('descendant')->references('id')->on('entities')->onDelete('cascade')->onUpdate('cascade');

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::drop('entities_closure');
    }
}
