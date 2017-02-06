<?php
namespace DreamFactory\Core\Notification\Resources;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Notification\Models\NotificationAppDevice;
use DreamFactory\Core\Resources\BaseRestResource;
use Sly\NotificationPusher\PushManager;
use Sly\NotificationPusher\Adapter\AdapterInterface;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Models\App;
use DreamFactory\Core\Exceptions\InternalServerErrorException;

class BaseResource extends BaseRestResource
{
    /** A resource identifier used in swagger doc. */
    const RESOURCE_IDENTIFIER = 'name';

    /** @var null | PushManager */
    protected $pushManager = null;

    /** @var null | AdapterInterface */
    protected $pushAdapter = null;

    /**
     * {@inheritdoc}
     */
    protected static function getResourceIdentifier()
    {
        return static::RESOURCE_IDENTIFIER;
    }

    /**
     * @return null|\Sly\NotificationPusher\PushManager
     */
    protected function getPushManager()
    {
        if (empty($this->pushManager)) {
            $this->pushManager = $this->getParent()->getPushManager();
        }

        return $this->pushManager;
    }

    /**
     * @return null|\Sly\NotificationPusher\Adapter\AdapterInterface
     */
    protected function getPushAdapter()
    {
        if (empty($this->pushAdapter)) {
            $this->pushAdapter = $this->getParent()->getPushAdapter();
        }

        return $this->pushAdapter;
    }

    /**
     * @return string
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     */
    protected function getMessage()
    {
        $message = $this->request->getPayloadData('message');
        if (empty($message)) {
            throw new BadRequestException('No message found. Please provide a message for your push notification.');
        }

        return $message;
    }

    /**
     * @param bool $throw
     *
     * @return string|null
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     */
    protected function getApiKey($throw = false)
    {
        $apiKey = $this->request->getPayloadData('api_key');
        if (empty($apiKey)) {
            $apiKey = Session::getApiKey();
        }
        if (empty($apiKey) && $throw === true) {
            throw new BadRequestException('No API Key found. Please provide a valid API Key.');
        }

        return $apiKey;
    }

    /**
     * @return array
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     */
    protected function getDeviceToken()
    {
        $apiKey = $this->getApiKey();
        $deviceToken = $this->request->getPayloadData('device_token');
        if (empty($deviceToken)) {
            if (empty($apiKey)) {
                throw new BadRequestException(
                    'No API Key and/or Device Token found. ' .
                    'Please provide a valid API Key or Device Token for your push notification.'
                );
            }
            $deviceToken = $this->getDeviceTokenByApiKey($apiKey);
        }

        if (!is_array($deviceToken)) {
            $deviceToken = [$deviceToken];
        }

        return $deviceToken;
    }

    /**
     * @param $apiKey
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function getDeviceTokenByApiKey($apiKey)
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
     * @param $appId
     *
     * @return array
     */
    protected function fetchAppDeviceMapping($appId)
    {
        $serviceId = $this->getParent()->getServiceId();
        $appDeviceToken = NotificationAppDevice::where('app_id', $appId)
            ->where('service_id', $serviceId)
            ->get();

        return $appDeviceToken->toArray();
    }

    /**
     * @param $apiKey
     *
     * @return int
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function getAppId($apiKey)
    {
        $appId = App::getAppIdByApiKey($apiKey);
        if (empty($appId)) {
            throw new InternalServerErrorException(
                'Unexpected error occurred. No App ID found for API Key provided. ' .
                'Please clear DreamFactory cache and try again.'
            );
        }

        return $appId;
    }
}