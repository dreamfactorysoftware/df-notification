<?php

namespace DreamFactory\Core\Notification\Services;

use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Models\App;
use DreamFactory\Core\Notification\Models\NotificationAppDevice;
use DreamFactory\Core\Services\BaseRestService;
use Sly\NotificationPusher\Adapter\AdapterInterface;
use Sly\NotificationPusher\Model\Message;
use Sly\NotificationPusher\PushManager;
use Sly\NotificationPusher\Collection\DeviceCollection;
use Sly\NotificationPusher\Model\Push as Pusher;
use Illuminate\Support\Arr;

abstract class BaseService extends BaseRestService
{
    /** @var null | PushManager */
    protected $pushManager = null;

    /** @var null | AdapterInterface */
    protected $pushAdapter = null;

    /**
     * BaseService constructor.
     *
     * @param array $settings
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function __construct(array $settings = [])
    {
        parent::__construct($settings);

        if (empty($this->config)) {
            throw new InternalServerErrorException('No service configuration found for notification service.');
        }

        $this->setPusher($this->config);
    }

    /** {@inheritdoc} */
    public function getAccessList()
    {
        if (!empty($this->getPermissions())) {
            $list = ['', '*'];
            $resources = $this->getResources();
            foreach ($resources as $resource) {
                $list[] = Arr::get($resource, 'name') . '/';
            }

            return $list;
        }

        return [];
    }

    /**
     * Sets the push manager and push adapter to be used in resources.
     *
     * @param $config
     *
     * @throws InternalServerErrorException
     */
    abstract protected function setPusher($config);

    /**
     * @param string $token
     *
     * @return \Sly\NotificationPusher\Model\Device
     */
    abstract public function getDevice($token);

    /**
     * Returns the push manager.
     *
     * @return null|\Sly\NotificationPusher\PushManager
     */
    public function getPushManager()
    {
        return $this->pushManager;
    }

    /**
     * Returns the push adapter.
     *
     * @return null|\Sly\NotificationPusher\Adapter\AdapterInterface
     */
    public function getPushAdapter()
    {
        return $this->pushAdapter;
    }

    /**
     * Returns service name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Pushes notification.
     *
     * @param \Sly\NotificationPusher\Model\Message               $message
     * @param \Sly\NotificationPusher\Collection\DeviceCollection $devices
     *
     * @return \Sly\NotificationPusher\Collection\PushCollection
     */
    public function push(Message $message, DeviceCollection $devices)
    {
        $push = new Pusher($this->getPushAdapter(), $devices, $message);
        $this->getPushManager()->add($push);
        $result = $this->getPushManager()->push();

        return $result;
    }

    /**
     * Gets device token by DF API Key.
     *
     * @param $apiKey
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function getDeviceTokenByApiKey($apiKey)
    {
        if (empty($apiKey)) {
            throw new InternalServerErrorException(
                'Invalid API Key. Valid API Key is required for retrieving Device Tokens.'
            );
        }
        $appId = App::getAppIdByApiKey($apiKey);
        if (empty($appId)) {
            throw new InternalServerErrorException(
                'Unexpected error occurred. No App ID found for API Key provided. ' .
                'Please clear DreamFactory cache and try again.'
            );
        }

        $records = $this->fetchAppDeviceMapping($appId);
        $out = [];
        foreach ($records as $record) {
            $out[] = $record['device_token'];
        }

        return $out;
    }

    /**
     * Fetches device tokens by app id.
     *
     * @param $appId
     *
     * @return array
     */
    public function fetchAppDeviceMapping($appId)
    {
        $serviceId = $this->getServiceId();
        $appDeviceToken = NotificationAppDevice::where('app_id', $appId)
            ->where('service_id', $serviceId)
            ->get();

        return $appDeviceToken->toArray();
    }

    /**
     * Send push notification by API Key
     *
     * @param $message
     * @param $apiKey
     *
     * @return \Sly\NotificationPusher\Collection\PushCollection
     * @throws \DreamFactory\Core\Exceptions\NotFoundException
     */
    public function pushByApiKey($message, $apiKey)
    {
        $devices = $this->getDeviceTokenByApiKey($apiKey);
        if (empty($devices)) {
            throw new NotFoundException(
                'Failed to send push notification. No registered devices found for your application.'
            );
        }
        foreach ($devices as $key => $token) {
            $devices[$key] = $this->getDevice($token);
        }
        $deviceCollection = new DeviceCollection($devices);
        $message = new Message($message);

        return $this->push($message, $deviceCollection);
    }
}