<?php
namespace DreamFactory\Core\Notification\Resources;

class GCMPush extends BaseResource
{
    /** Resource name constant */
    const RESOURCE_NAME = 'push';

    /**
     * Handles sending android push notification.
     *
     * @return array
     */
    protected function handlePOST()
    {
        $message = $this->getPushMessage();
        $devices = $this->getDeviceCollection(true);
        $result = $this->push($message, $devices);

        return ['count' => $result->count()];
    }
}