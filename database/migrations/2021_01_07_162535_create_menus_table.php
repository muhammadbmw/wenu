<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
			$table->string('name',100);
			$table->string('description');
			$table->string('ingredients')->nullable();
			$table->string('image')->nullable();
			$table->decimal('price',$precision = 6,$scale = 2);
            $table->timestamps();
			$table->string('status',10)->default('active');
			$table->engine = 'InnoDB';
			$table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus');
    }
}
