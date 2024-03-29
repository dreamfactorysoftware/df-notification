<?php
namespace DreamFactory\Core\Notification\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;
use Sly\NotificationPusher\PushManager;
use DreamFactory\Core\Components\ServiceEventMapper;
use DreamFactory\Core\Models\ServiceEventMap;

class GcmConfig extends BaseServiceConfigModel
{
    use ServiceEventMapper;

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
    public static function getConfigSchema()
    {
        $schema = parent::getConfigSchema();
        $sem = ServiceEventMap::getConfigSchema();
        $sem[1]['label'] = 'Message';
        $schema[] = [
            'name'        => 'service_event_map',
            'label'       => 'Service Event',
            'description' => 'Select event(s) that will send notifications.',
            'type'        => 'array',
            'required'    => false,
            'allow_null'  => true,
            'items'       => $sem,
        ];

        return $schema;
    }

    /**
     * {@inheritdoc}
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'api_key':
                $schema['label'] = 'API Key';
                $schema['description'] = 'Please enter your FCM Server API Key.';
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