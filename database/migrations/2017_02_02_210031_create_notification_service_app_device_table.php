<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationServiceAppDeviceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driver = Schema::getConnection()->getDriverName();
        // Even though we take care of this scenario in the code,
        // SQL Server does not allow potential cascading loops,
        // so set the default no action and clear out created/modified by another user when deleting a user.
        $userOnDelete = (('sqlsrv' === $driver) ? 'no action' : 'set null');

        Schema::create(
            'notification_app_device',
            function (Blueprint $t) use ($userOnDelete){
                $t->increments('id');
                $t->integer('service_id')->unsigned();
                $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                $t->integer('app_id')->unsigned()->nullable();
                $t->foreign('app_id')->references('id')->on('app')->onDelete('cascade');
                $t->text('device_token');
                $t->timestamp('created_date')->nullable();
                $t->timestamp('last_modified_date')->useCurrent();
                $t->integer('created_by_id')->unsigned()->nullable();
                $t->foreign('created_by_id')->references('id')->on('user')->onDelete($userOnDelete);
                $t->integer('last_modified_by_id')->unsigned()->nullable();
                $t->foreign('last_modified_by_id')->references('id')->on('user')->onDelete($userOnDelete);
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
        Schema::dropIfExists('notification_app_device');
    }
}
