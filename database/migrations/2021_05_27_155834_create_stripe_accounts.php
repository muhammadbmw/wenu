<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStripeAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stripe_accounts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
			$table->boolean('details_submitted');
            $table->string('account_id');
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
        Schema::dropIfExists('stripe_accounts');
    }
}
