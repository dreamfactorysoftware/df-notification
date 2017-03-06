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
    public static function getApiDocInfo($service, array $resource = [])
    {
        $base = parent::getApiDocInfo($service, $resource);
        $serviceName = strtolower($service);
        $class = trim(strrchr(static::class, '\\'), '\\');
        $resourceName = strtolower(array_get($resource, 'name', $class));
        $path = '/' . $serviceName . '/' . $resourceName;
        unset($base['paths'][$path]['get']);
        $base['paths'][$path]['post'] = [
            'tags'        => [$serviceName],
            'summary'     => 'sendPushNotification() - Perform authentication',
            'operationId' => 'sendPushNotification',
            'consumes'    => ['application/json', 'application/xml'],
            'produces'    => ['application/json', 'application/xml'],
            'description' => 'Sends push notifications',
            'parameters'  => [
                [
                    'name'        => 'api_key',
                    'type'        => 'string',
                    'description' => 'DreamFactory application API Key',
                    'in'          => 'query',
                    'required'    => false,
                ],
                [
                    'name'        => 'body',
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
                    'in'          => 'body',
                    'required'    => true
                ]
            ],
            'responses'   => [
                '200'     => [
                    'description' => 'Success',
                    'schema'      => [
                        'type'       => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean']
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