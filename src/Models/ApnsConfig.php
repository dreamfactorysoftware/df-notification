<?php
namespace DreamFactory\Core\Notification\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;
use Sly\NotificationPusher\PushManager;

class ApnsConfig extends BaseServiceConfigModel
{
    protected $table = 'apns_config';

    /** @var array */
    protected $fillable = [
        'service_id',
        'certificate',
        'passphrase',
        'environment'
    ];

    /** @var array */
    protected $casts = [
        'service_id' => 'integer'
    ];

    protected $protected = ['pass_phrase'];

    protected $encrypted = ['pass_phrase'];

    /**
     * {@inheritdoc}
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'certificate':
                $schema['description'] = 'Please provide the path to your iOS APNS certificate (.pem) file.';
                break;
            case 'pass_phrase':
                $schema['description'] = 'Enter your Pass Phrase for the certificate file.';
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
                $schema['description'] = 'Please select your iOS application environment';
                break;
        }
    }
}