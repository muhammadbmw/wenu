<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('city',50);
            $table->string('address',100);
			$table->string('province',20);
            $table->string('postal_code',10);
            $table->string('mobile',10);
            $table->timestamps();
            $table->engine = 'InnoDB';
            $table->unsignedBigInteger('user_id');
			$table->foreign('user_id')->references('id')->on('users')  ->onUpdate('cascade')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profiles');
    }
}
