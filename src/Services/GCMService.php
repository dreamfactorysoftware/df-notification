<?php
namespace DreamFactory\Core\Notification\Services;

use DreamFactory\Core\Notification\Resources\GCMPush as PushResource;
use DreamFactory\Core\Notification\Resources\Register as RegisterResource;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use Sly\NotificationPusher\PushManager;
use Sly\NotificationPusher\Adapter\Gcm;

class GCMService extends BaseService
{
    /** @type array Service Resources */
    protected static $resources = [
        PushResource::RESOURCE_NAME     => [
            'name'       => PushResource::RESOURCE_NAME,
            'class_name' => PushResource::class,
            'label'      => 'Push'
        ],
        RegisterResource::RESOURCE_NAME => [
            'name'       => RegisterResource::RESOURCE_NAME,
            'class_name' => RegisterResource::class,
            'label'      => 'Register'
        ],
    ];

    /** {@inheritdoc} */
    public function getResources($onlyHandlers = false)
    {
        return ($onlyHandlers) ? static::$resources : array_values(static::$resources);
    }

    /** {@inheritdoc} */
    protected function setPusher($config)
    {
        $environment = array_get($config, 'environment');
        if (empty($environment)) {
            throw new InternalServerErrorException(
                'Missing application environment. Please check service configuration for Environment config.'
            );
        }
        $apiKey = array_get($config, 'api_key');
        if (empty($apiKey)) {
            throw new InternalServerErrorException(
                'GCM Server API Key not found. ' .
                'Please check service configuration for API Key config.'
            );
        }

        $this->pushManager = new PushManager($environment);
        $this->pushAdapter = new Gcm(['apiKey' => $apiKey]);
    }
}