<?php

namespace DreamFactory\Core\Notification\Resources;

use Str;

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
        $option = [];
        $payloadKey = [
            'title' => null,
            'subtitle' => null,
            'launch-image' => null,
            'title-loc-key' => null,
            'title-loc-args' => null,
            'subtitle-loc-key' => null,
            'subtitle-loc-args' => null,
            'badge' => 1,
            'sound' => 'default',
            'volume' => 1,
            'critical' => null,
            'category' => null,
            'content-available' => null,
            'mutable-content' => null,
            'url-args' => null,
            'custom' => null,
            'loc-key' => null,
            'loc-args' => null,
            'expire' => null,
        ];

        foreach ($payloadKey as $key => $default) {
            $payload = $this->getPayloadData($key, $default);
            if (!empty($payload)) {
                $option[$key] = $payload;
            }
        }

        return $option;
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