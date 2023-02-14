<?php

namespace DreamFactory\Core\Notification\Resources;

use Str;

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

        $allTokens = $devices->getIterator()->count();
        $sentTokens = $this->getSentTokens();

        return ['success' => $allTokens === $sentTokens];
    }

    /** {@inheritdoc} */
    protected function getMessageOption()
    {
        return $this->request->getPayloadData('data', []);
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
                'post' => [
                    'summary'     => 'Perform authentication',
                    'description' => 'Sends push notifications',
                    'operationId' => 'send' . $capitalized . 'PushNotification',
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
                        'content'     => [
                            'application/json' => [
                                'schema' => [
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
                                        ],
                                    ],
                                ],
                            ],
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