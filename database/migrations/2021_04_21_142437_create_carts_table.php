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
			$table->decimal('price',$precision = 6,$scale = 2);
			$table->enum('pickupOrDelivery',['pickup','delivery']);
			$table->string('date',12);
			$table->string('available',100);
			$table->string('address')->nullable();
			$table->string('driver_notes')->nullable();
            $table->timestamps();
			$table->engine = 'InnoDB';
			$table->foreignId('menu_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
			$table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
			$table->unsignedBigInteger('chef_id');
			$table->foreign('chef_id')->references('id')->on('users') ->onUpdate('cascade')->onDelete('cascade');
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
