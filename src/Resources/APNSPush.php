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
        try {
            $message = $this->getPushMessage();
            $devices = $this->getDeviceCollection();
            $this->push($message, $devices);
            // Clear temporary certificate file.
            $this->getParent()->deleteCertificateFile();

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}