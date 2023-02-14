<?php

namespace DreamFactory\Core\Notification\Resources;

use Illuminate\Support\Str;

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

        $allTokens = $devices->getIterator()->count();
        $sentTokens = $this->getSentTokens();
        
        return ['success' => $allTokens === $sentTokens];
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
            'action-loc-key' => null,
            'badge' => 1,
            'sound' => 'default',
            'volume' => 1,
            'critical' => null,
            'category' => null,
            'content-available' => null,
            'mutable-content' => null,
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
                                        'device_token'    => [
                                            'type'        => 'string',
                                            'description' => 'Target device token. ' .
                                            'Only required when no API Key is provided or to ignore target devices by API Key.'
                                        ],
                                        'title'           => [
                                            'type'        => 'string',
                                            'description' => 'The title of the notification'
                                        ],
                                        'subtitle'        => [
                                            'type'        => 'string',
                                            'description' => 'Additional information that explains the purpose of the notification.'
                                        ],
                                        'message'         => [
                                            'type'        => 'string',
                                            'description' => 'Push notification message'
                                        ],
                                        'launch-image'    => [
                                            'type'        => 'string',
                                            'description' => 'The name of the launch image file to display.'
                                        ],
                                        'badge'           => [
                                            'type'        => 'integer',
                                            'description' => 'Number to show on app icon badge.'
                                        ],
                                        'sound'           => [
                                            'type'        => 'string',
                                            'description' => 'Notification sound to play.'
                                        ],
                                        'volume'          => [
                                            'type'        => 'integer',
                                            'description' => 'The volume for the critical alert\'s sound. ' . 
                                            'Set this to a value between 0 and 1.'
                                        ],
                                        'critical'        => [
                                            'type'        => 'boolean',
                                            'description' => 'The critical alert flag.'
                                        ],
                                        'action-loc-key'  => [
                                            'type'        => 'string',
                                            'description' => 'Localized action button title. ' .
                                            'Provide a localized string from your app\'s Localizable.strings file.'
                                        ],
                                        'loc-key'         => [
                                            'type'        => 'string',
                                            'description' => 'Any localized string from your app\'s Localizable.strings file.'
                                        ],
                                        'loc-args'        => [
                                            'type'        => 'array',
                                            'items'       => ['type' => 'string'],
                                            'description' => 'Replace values for your localized key.'
                                        ],
                                        'title-loc-key'   => [
                                            'type'        => 'string',
                                            'description' => 'The key for a localized title string.'
                                        ],
                                        'title-loc-args'  => [
                                            'type'        => 'array',
                                            'items'       => ['type' => 'string'],
                                            'description' => 'Replace values for your localized title key.'
                                        ],
                                        'subtitle-loc-key'=> [
                                            'type'        => 'string',
                                            'description' => 'The key for a localized subtitle string.'
                                        ],
                                        'subtitle-loc-args'=> [
                                            'type'        => 'array',
                                            'items'       => ['type' => 'string'],
                                            'description' => 'Replace values for your localized subtitle key.'
                                        ],
                                        'category'        => [
                                            'type'        => 'string',
                                            'description' => 'The notification\'s type.'
                                        ],
                                        'content-available'=> [
                                            'type'        => 'boolean',
                                            'description' => 'The background notification flag. ' . 
                                            'To perform a silent background update, specify the value 1 and don\'t include the alert, ' .
                                            'badge, or sound keys in your payload.'
                                        ],
                                        'mutable-content' => [
                                            'type'        => 'boolean',
                                            'description' => 'The notification service app extension flag. ' .
                                            'If the value is true, the system passes the notification to your notification service app extension before delivery.'
                                        ],
                                        'custom'          => [
                                            'type'        => 'array',
                                            'items'       => ['type' => 'object'],
                                            'description' => 'Any custom data to send with notification.'
                                        ],
                                        'expire'          => [
                                            'type'        => 'string',
                                            'description' => 'The date at which the notification is no longer valid. ' . 
                                            'This refers to a date in UNIX epoch format, which is measured in seconds and based on UTC. ' .
                                            'If the value is nonzero, APNs tries to deliver it at least once. ' . 
                                            'If the value is 0, APNs attempts to deliver the notification only once and doesn\'t store it.'
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