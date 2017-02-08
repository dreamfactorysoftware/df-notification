<?php
namespace DreamFactory\Core\Notification\Resources;

class APNSPush extends BaseResource
{
    /** Resource name constant */
    const RESOURCE_NAME = 'push';

    /**
     * Handles sending iOS push notification.
     *
     * @return array
     */
    protected function handlePOST()
    {
        $message = $this->getPushMessage();
        $devices = $this->getDeviceCollection();
        $result = $this->push($message, $devices);
        $this->getParent()->deleteCertificateFile();

        return ['count' => $result->count()];
    }
}