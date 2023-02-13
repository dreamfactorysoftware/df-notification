<?php

namespace DreamFactory\Core\Notification\Resources;

use Sly\NotificationPusher\Adapter\AdapterInterface;
use Sly\NotificationPusher\PushManager;
use Str;

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

    /** {@inheritdoc} */
    protected function getApiDocPaths()
    {
        $service = $this->getServiceName();
        $capitalized = Str::camel($service);
        $resourceName = strtolower($this->name);
        $path = '/' . $resourceName;
        $base = [
            $path => [
                'get' => [
                    'summary'     => 'Get feedback from APNS server',
                    'description' => 'Retrieves push notification feedback information from APNS server.',
                    'operationId' => 'get' . $capitalized . 'Feedback',
                    'responses'   => [
                        '200' => [
                            'description' => 'Success',
                            'content'     => [
                                'application/json' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'feedback' => [
                                                'type'  => 'array',
                                                'items' => [
                                                    'type' => 'object'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
            ],
        ];

        return $base;
    }
}