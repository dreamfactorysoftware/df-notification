<?php
namespace DreamFactory\Core\Notification\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;
use Sly\NotificationPusher\PushManager;

class GcmConfig extends BaseServiceConfigModel
{
    protected $table = 'gcm_config';

    /** @var array */
    protected $fillable = [
        'service_id',
        'api_key',
        'environment'
    ];

    /** @var array */
    protected $casts = [
        'service_id' => 'integer'
    ];

    /** @var array */
    protected $encrypted = ['api_key'];

    /**
     * {@inheritdoc}
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'api_key':
                $schema['label'] = 'API Key';
                $schema['description'] = 'Please enter your GCM Server API Key.';
                break;
            case 'environment':
                $schema['type'] = 'picklist';
                $schema['values'] = [
                    [
                        'label' => 'Development',
                        'name'  => PushManager::ENVIRONMENT_DEV
                    ],
                    [
                        'label' => 'Production',
                        'name'  => PushManager::ENVIRONMENT_PROD
                    ]
                ];
                $schema['description'] = 'Please select your application environment';
                break;
        }
    }
}