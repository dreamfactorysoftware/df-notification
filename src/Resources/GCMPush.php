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
        $devices = $this->getDeviceCollection();
        $this->push($message, $devices);

        return ['success' => true];
    }

    /** {@inheritdoc} */
    protected function getMessageOption()
    {
        return $this->request->getPayloadData('data', []);
    }

    /** {@inheritdoc} */
    protected function getApiDocPaths()
    {
        $resourceName = strtolower($this->name);
        $path = '/' . $resourceName;
        $base = [
            $path => [
                'post' => [
                    'summary'     => 'sendPushNotification() - Perform authentication',
                    'operationId' => 'sendPushNotification',
                    'description' => 'Sends push notifications',
                    'parameters'  => [
                        [
                            'name'        => 'api_key',
                            'schema'      => ['type' => 'string'],
                            'description' => 'DreamFactory application API Key',
                            'in'          => 'query',
                        ],
                    ],
                    'requestBody' => [
                        'description' => 'Content - Notification message and target device',
                        'schema'      => [
                            'type'       => 'object',
                            'properties' => [
                                'message'      => [
                                    'type'        => 'string',
                                    'description' => 'Push notification message'
                                ],
                                'data'         => [
                                    'type'        => 'object',
                                    'description' => 'Any custom data to send with notification.'
                                ],
                                'device_token' => [
                                    'type'        => 'string',
                                    'description' => 'Target device token. ' .
                                        'Only required when no API Key is provided or to ignore target devices by API Key.'
                                ]
                            ]
                        ],
                        'required'    => true
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/Success']
                    ],
                ],
            ],
        ];

        return $base;
    }
}