<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
			$table->integer('quantity');
			$table->string('cook_notes')->nullable();
			$table->string('status',10)->default('active');
			$table->enum('pickupOrDelivery',['pickup','delivery']);
            $table->timestamps();
			$table->engine = 'InnoDB';
			$table->foreignId('menu_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('carts');
    }
}
