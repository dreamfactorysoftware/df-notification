<?php
namespace DreamFactory\Core\Notification\Models;

use DreamFactory\Core\Models\BaseModel;

class NotificationAppDevice extends BaseModel
{
    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_date';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'last_modified_date';

    /** @var string */
    protected $table = 'notification_app_device';

    /** @var array */
    protected $fillable = [
        'service_id',
        'app_id',
        'device_token'
    ];

    protected $encrypted = ['device_token'];

    /**
     * @var bool
     */
    public $incrementing = true;
}