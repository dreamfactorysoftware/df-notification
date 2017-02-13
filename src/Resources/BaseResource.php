<?php
namespace DreamFactory\Core\Notification\Resources;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Notification\Services\BaseService;
use DreamFactory\Core\Resources\BaseRestResource;
use Sly\NotificationPusher\PushManager;
use Sly\NotificationPusher\Adapter\AdapterInterface;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Models\App;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\NotFoundException;
use Sly\NotificationPusher\Collection\DeviceCollection;
use Sly\NotificationPusher\Model\Message;

/**
 * Class BaseResource
 *
 * @method BaseService getParent()
 * @package DreamFactory\Core\Notification\Resources
 */
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
     * Returns the push manager from the push service.
     *
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
     * Returns the push adapter from the push service.
     *
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
     * Gets the push message from request.
     *
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
     * Gets the push message model.
     *
     * @return \Sly\NotificationPusher\Model\Message
     */
    protected function getPushMessage()
    {
        $message = $this->getMessage();

        return new Message($message, $this->getMessageOption());
    }

    /**
     * Gets the message options (badge, sound, custom data etc.)
     *
     * @return array
     */
    protected function getMessageOption()
    {
        return [];
    }

    /**
     * Gets the DF API Key from request.
     *
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
     * Gets device token from request or by DF API Key.
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     */
    protected function getDeviceToken()
    {
        $deviceToken = $this->request->getPayloadData('device_token');
        if (empty($deviceToken)) {
            $apiKey = $this->getApiKey();
            if (empty($apiKey)) {
                throw new BadRequestException(
                    'No API Key and/or Device Token found. ' .
                    'Please provide a valid API Key or Device Token for your push notification.'
                );
            }
            $deviceToken = $this->getParent()->getDeviceTokenByApiKey($apiKey);
        }

        if (!is_array($deviceToken)) {
            $deviceToken = [$deviceToken];
        }

        return $deviceToken;
    }

    /**
     * Returns push device collection based on request (device tokens).
     *
     * @return \Sly\NotificationPusher\Collection\DeviceCollection
     * @throws \DreamFactory\Core\Exceptions\NotFoundException
     */
    protected function getDeviceCollection()
    {
        $deviceToken = $this->getDeviceToken();
        if (empty($deviceToken)) {
            throw new NotFoundException(
                'Failed to send push notification. No registered devices found for your application.'
            );
        }
        foreach ($deviceToken as $key => $token) {
            $deviceToken[$key] = $this->getParent()->getDevice($token);
        }

        return new DeviceCollection($deviceToken);
    }

    /**
     * Gets app id by API Key.
     *
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

    /**
     * Pushes notification.
     *
     * @param \Sly\NotificationPusher\Model\Message               $message
     * @param \Sly\NotificationPusher\Collection\DeviceCollection $devices
     *
     * @return \Sly\NotificationPusher\Collection\PushCollection
     */
    protected function push(Message $message, DeviceCollection $devices)
    {
        return $this->getParent()->push($message, $devices);
    }
}