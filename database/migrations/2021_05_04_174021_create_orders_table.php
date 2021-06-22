<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
			$table->string('status',20)->default('active');
            $table->timestamps();
			$table->engine = 'InnoDB';
			$table->foreignId('payment_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
			$table->unsignedBigInteger('chef_id');
			$table->foreign('chef_id')->references('id')->on('users') ->onUpdate('cascade')->onDelete('cascade');
			$table->unsignedBigInteger('foodie_id');
			$table->foreign('foodie_id')->references('id')->on('users') ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
