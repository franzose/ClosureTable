<?php
declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntitiesTable extends Migration
{
    public function up(): void
    {
        Schema::create('entities', static function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->string('title')->default('The Title');
            $table->string('excerpt')->default('');
            $table->string('body')->default('');
            $table->integer('position', false, true);
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('entities')->onDelete('set null');

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::drop('entities');
    }
}
