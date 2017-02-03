<?php
namespace DreamFactory\Core\Notification\Services;

namespace DreamFactory\Core\Notification\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use Sly\NotificationPusher\Adapter\Apns;
use Sly\NotificationPusher\PushManager;
use DreamFactory\Core\Notification\Resources\Push as PushResource;
use DreamFactory\Core\Notification\Resources\Register as RegisterResource;

class APNService extends BaseService
{
    /** @inheritdoc */
    public function getResources($onlyHandlers = false)
    {
        return ($onlyHandlers) ? static::$resources : array_values(static::$resources);
    }

    /** @type array Service Resources */
    protected static $resources = [
        PushResource::RESOURCE_NAME => [
            'name'       => PushResource::RESOURCE_NAME,
            'class_name' => PushResource::class,
            'label'      => 'Push'
        ],
        RegisterResource::RESOURCE_NAME      => [
            'name'       => RegisterResource::RESOURCE_NAME,
            'class_name' => RegisterResource::class,
            'label'      => 'Register'
        ],
    ];

    protected function setPusher($config)
    {
        $environment = array_get($config, 'environment');
        if (empty($environment)) {
            throw new InternalServerErrorException(
                'Missing application environment. Please check service configuration for Environment config.'
            );
        }
        $certificate = storage_path(array_get($config, 'certificate'));
        if (!file_exists($certificate)) {
            throw new InternalServerErrorException(
                'Certificate file not found at [' .
                $certificate .
                ']. Please check service configuration for Certificate config.');
        }

        $this->pushManager = new PushManager($environment);
        $this->pushAdapter = new Apns(['certificate' => $certificate]);

    }
}