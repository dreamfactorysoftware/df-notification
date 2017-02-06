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
        'environment'
    ];

    /** @var array */
    protected $casts = [
        'service_id' => 'integer'
    ];

    /** @var array */
    protected $encrypted = ['certificate'];

    /**
     * {@inheritdoc}
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'certificate':
                $schema['type'] = 'file_certificate';
                $schema['label'] = 'Certificate file (.pem)';
                $schema['description'] = 'Please provide your iOS APNS certificate (.pem) file. ' .
                    'If you have the pkcs12 (.p12) file from Apple keychain access, you can convert it to .pem ' .
                    'file using the following command. ' .
                    '<br><pre>' .
                    'openssl pkcs12 -in my-certificate-file.p12 -out my-certificate-file.pem -nodes -clcerts' .
                    '</pre>' .
                    'Replace  my-certificate-file.p12 with the name of the certificate file you exported ' .
                    'from Keychain Access.';
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