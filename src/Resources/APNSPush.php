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
    protected function getApiDocPaths()
    {
        $service = $this->getServiceName();
        $capitalized = camelize($service);
        $resourceName = strtolower($this->name);
        $path = '/' . $resourceName;
        $base = [
            $path => [
                'post' => [
                    'summary'     => 'Send push notification',
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