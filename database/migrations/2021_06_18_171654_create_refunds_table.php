<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
			$table->decimal('amount',$precision = 6,$scale = 2);
			$table->string('refund_id',100);
            $table->timestamps();
			$table->foreignId('order_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('refunds');
    }
}
