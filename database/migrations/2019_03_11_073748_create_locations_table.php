<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

       if(!Schema::hasTable('locations')){ 

            Schema::create('locations', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('type');
                $table->float('latitude');
                $table->float('longitude');

                $table->integer('sensor_id')->nullable();
                $table->float('sensor_height')->nullable();
                $table->float('watch_level')->nullable();
                $table->float('warning_level')->nullable();
                $table->float('severe_level')->nullable();
                $table->string('status');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('deleted_at')->nullable();
            });
        }



    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('locations');
    }
}
