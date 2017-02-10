<?php

namespace DreamFactory\Core\Notification\Resources;

use Sly\NotificationPusher\Adapter\AdapterInterface;
use Sly\NotificationPusher\PushManager;

class APNFeedback extends BaseResource
{
    /** Resource name constant */
    const RESOURCE_NAME = 'feedback';

    /**
     * Handles fetching APNS feedback.
     *
     * @return array
     */
    protected function handleGET()
    {
        /** @var PushManager $manager */
        $manager = $this->getParent()->getPushManager();
        /** @var AdapterInterface $adapter */
        $adapter = $this->getParent()->getPushAdapter();
        $feedback = $manager->getFeedback($adapter);

        return ['feedback' => $feedback];
    }
}