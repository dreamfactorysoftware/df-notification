<?php
namespace DreamFactory\Core\Notification\Services;

use DreamFactory\Core\Notification\Resources\GCMPush as PushResource;
use DreamFactory\Core\Notification\Resources\Register as RegisterResource;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Notification\Adapters\FCMAdapter;
use Sly\NotificationPusher\Model\Device;
use Sly\NotificationPusher\PushManager;
use Illuminate\Support\Arr;

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
    public function getDevice($token)
    {
        return new Device($token);
    }

    /** {@inheritdoc} */
    protected function setPusher($config)
    {
        $environment = Arr::get($config, 'environment');
        if (empty($environment)) {
            throw new InternalServerErrorException(
                'Missing application environment. Please check service configuration for Environment config.'
            );
        }
        $apiKey = Arr::get($config, 'api_key');
        if (empty($apiKey)) {
            throw new InternalServerErrorException(
                'GCM Server API Key not found. ' .
                'Please check service configuration for API Key config.'
            );
        }

        $this->pushManager = new PushManager($environment);
        $this->pushAdapter = new FCMAdapter(['apiKey' => $apiKey]);
    }
}