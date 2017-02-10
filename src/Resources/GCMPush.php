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
        try {
            $message = $this->getPushMessage();
            $devices = $this->getDeviceCollection();
            $this->push($message, $devices);

            return ['success' => true];
        } catch (\Exception $e){
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}