<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stops', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->double('lat', 11, 8);
            $table->double('lng', 11, 8);
            $table->integer('city_id')->unsigned();
            $table->integer('vehicle_type_id')->unsigned();
            $table->string('link_numbers')->nullable();

            //foreign keys
            $table->foreign('city_id')->references('id')->on('cities');
            $table->foreign('vehicle_type_id')->references('id')->on('vehicle_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('stops');
    }
}