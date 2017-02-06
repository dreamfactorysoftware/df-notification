<?php
namespace DreamFactory\Core\Notification\Resources;

use DreamFactory\Core\Exceptions\NotFoundException;
use Sly\NotificationPusher\Collection\DeviceCollection;
use Sly\NotificationPusher\Model\Device;
use Sly\NotificationPusher\Model\Message;
use Sly\NotificationPusher\Model\Push as Pusher;

class Push extends BaseResource
{
    /** Resource name constant */
    const RESOURCE_NAME = 'push';

    /**
     * @return array
     */
    protected function handlePOST()
    {
        $message = $this->getApnMessage();
        $devices = $this->getApnDevices();
        $result = $this->push($message, $devices);
        $this->getParent()->deleteCertificateFile();

        return ['count' => $result->count()];
    }

    /**
     * @return \Sly\NotificationPusher\Model\Message
     */
    protected function getApnMessage()
    {
        $message = $this->getMessage();

        return new Message($message);
    }

    /**
     * @return \Sly\NotificationPusher\Collection\DeviceCollection
     * @throws \DreamFactory\Core\Exceptions\NotFoundException
     */
    protected function getApnDevices()
    {
        $deviceToken = $this->getDeviceToken();
        if (empty($deviceToken)) {
            throw new NotFoundException(
                'Failed to push notification. No registered devices found for your application.'
            );
        }
        foreach ($deviceToken as $key => $token) {
            $deviceToken[$key] = new Device(strtolower($token));
        }

        return new DeviceCollection($deviceToken);
    }

    /**
     * @param \Sly\NotificationPusher\Model\Message               $message
     * @param \Sly\NotificationPusher\Collection\DeviceCollection $devices
     *
     * @return \Sly\NotificationPusher\Collection\PushCollection
     */
    protected function push(Message $message, DeviceCollection $devices)
    {
        $push = new Pusher($this->getPushAdapter(), $devices, $message);
        $this->getPushManager()->add($push);
        $result = $this->getPushManager()->push();

        return $result;
    }
}