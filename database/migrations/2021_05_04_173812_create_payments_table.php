<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
			$table->decimal('total',$precision = 6,$scale = 2);
			$table->decimal('subtotal',$precision = 6,$scale = 2);
			$table->decimal('tax',$precision = 6,$scale = 2);
			$table->decimal('service_fee',$precision = 6,$scale = 2);
			$table->decimal('application_fee',$precision = 6,$scale = 2);
			$table->decimal('transfer_amount',$precision = 6,$scale = 2);
			$table->string('payment_intent',100);
            $table->timestamps();
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
        Schema::dropIfExists('payments');
    }
}
