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
        $this->push($message, $devices);
        // Clear temporary certificate file.
        $this->getParent()->deleteCertificateFile();

        return ['success' => true];
    }

    /** {@inheritdoc} */
    protected function getMessageOption()
    {
        $badge = $this->request->getPayloadData('badge', 1);
        $sound = $this->request->getPayloadData('sound', 'default');
        $actionLocKey = $this->request->getPayloadData('action-loc-key');
        $locKey = $this->request->getPayloadData('loc-key');
        $locArgs = $this->request->getPayloadData('loc-args');
        $launchImage = $this->request->getPayloadData('launch-image');
        $custom = $this->request->getPayloadData('custom');

        $option = ['badge' => $badge, 'sound' => $sound];
        if (!empty($actionLocKey)) {
            $option['actionLocKey'] = $actionLocKey;
        }
        if (!empty($locKey)) {
            $option['locKey'] = $locKey;
        }
        if (!empty($locArgs)) {
            $option['locArgs'] = $locArgs;
        }
        if (!empty($launchImage)) {
            $option['launchImage'] = $launchImage;
        }
        if (!empty($custom)) {
            $option['custom'] = $custom;
        }

        return $option;
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
            'summary'     => 'sendPushNotification() - Send push notification',
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
                            'message'        => [
                                'type'        => 'string',
                                'description' => 'Push notification message'
                            ],
                            'badge'          => [
                                'type'        => 'integer',
                                'description' => 'Number to show on app icon badge.'
                            ],
                            'sound'          => [
                                'type'        => 'string',
                                'description' => 'Notification sound to play.'
                            ],
                            'action-loc-key' => [
                                'type'        => 'string',
                                'description' => 'Localized action button title. ' .
                                    'Provide a localized string from your app\'s Localizable.strings file.'
                            ],
                            'loc-key'        => [
                                'type'        => 'string',
                                'description' => 'Any localized string from your app\'s Localizable.strings file.'
                            ],
                            'loc-args'       => [
                                'type'        => 'array',
                                'items'       => ['type' => 'string'],
                                'description' => 'Replace values for your localized key.'
                            ],
                            'custom'         => [
                                'type'        => 'array',
                                'items'       => ['type' => 'object'],
                                'description' => 'Any custom data to send with notification.'
                            ],
                            'device_token'   => [
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