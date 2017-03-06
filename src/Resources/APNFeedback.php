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

    /** {@inheritdoc} */
    public static function getApiDocInfo($service, array $resource = [])
    {
        $base = parent::getApiDocInfo($service, $resource);
        $serviceName = strtolower($service);
        $class = trim(strrchr(static::class, '\\'), '\\');
        $resourceName = strtolower(array_get($resource, 'name', $class));
        $path = '/' . $serviceName . '/' . $resourceName;
        $base['paths'][$path]['get'] = [
            'tags'        => [$serviceName],
            'summary'     => 'getAPNSFeedback() - Get feedback from APNS server',
            'operationId' => 'getAPNSFeedback',
            'consumes'    => ['application/json', 'application/xml'],
            'produces'    => ['application/json', 'application/xml'],
            'description' => 'Retrieves push notification feedback information from APNS server.',
            'responses'   => [
                '200'     => [
                    'description' => 'Success',
                    'schema'      => [
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
                ],
                'default' => [
                    'description' => 'Error',
                    'schema'      => ['$ref' => '#/definitions/Error']
                ]
            ],
        ];

        return $base;
    }
}