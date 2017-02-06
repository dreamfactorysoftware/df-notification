<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApnsConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Create logstash config table
        Schema::create(
            'apns_config',
            function (Blueprint $t){
                $t->integer('service_id')->unsigned()->primary();
                $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                $t->longText('certificate');
                $t->string('environment');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Drop logstash config table if exists
        Schema::dropIfExists('apns_config');
    }
}
