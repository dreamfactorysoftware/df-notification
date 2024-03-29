<?php

namespace DreamFactory\Core\Notification\Resources;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\ConflictResourceException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Notification\Models\NotificationAppDevice;
use DreamFactory\Core\Utility\ResourcesWrapper;
use DreamFactory\Core\Utility\ResponseFactory;
use DreamFactory\Core\Contracts\ServiceResponseInterface;
use Str;

class Register extends BaseResource
{
    /** Resource name constant */
    const RESOURCE_NAME = 'register';

    /**
     * Handles fetching registered devices.
     *
     * @return array
     */
    protected function handleGET()
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            $serviceId = $this->getParent()->getServiceId();
            $records = NotificationAppDevice::where('service_id', $serviceId)->get();

            return ResourcesWrapper::wrapResources($records->toArray());
        } else {
            $appId = $this->getAppId($apiKey);

            return ResourcesWrapper::wrapResources($this->getParent()->fetchAppDeviceMapping($appId));
        }
    }

    /**
     * Handles device registration.
     *
     * @return \DreamFactory\Core\Utility\ServiceResponse
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \DreamFactory\Core\Exceptions\ConflictResourceException
     */
    protected function handlePOST()
    {
        $apiKey = $this->getApiKey(true);
        $deviceToken = $this->request->getPayloadData('device_token');
        if (empty($deviceToken)) {
            throw new BadRequestException('No Device Token found. Please provide a valid Device Token.');
        }
        $serviceId = $this->getParent()->getServiceId();
        $appId = $this->getAppId($apiKey);
        $existingTokens = $this->getParent()->getDeviceTokenByApiKey($apiKey);
        if (!in_array($deviceToken, $existingTokens)) {
            $model = NotificationAppDevice::create([
                'service_id'   => $serviceId,
                'app_id'       => $appId,
                'device_token' => $deviceToken
            ]);

            return ResponseFactory::create(['id' => $model->id], null, ServiceResponseInterface::HTTP_CREATED);
        } else {
            throw new ConflictResourceException('The Device Token is already registered for you application.');
        }
    }

    /**
     * Handles updating device registration.
     *
     * @return \DreamFactory\Core\Utility\ServiceResponse
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \DreamFactory\Core\Exceptions\NotFoundException
     */
    protected function handlePUT()
    {
        $apiKey = $this->getApiKey(true);
        $oldToken = $this->request->getPayloadData('old_token');
        if (empty($oldToken)) {
            throw new BadRequestException('Old/Existing token not found. Please provide the old token that you are replacing.');
        }
        $newToken = $this->request->getPayloadData('new_token', $this->request->getPayloadData('device_token'));
        if (empty($newToken)) {
            throw new BadRequestException('New token not found. Please provide the new token that you are replacing the old token with.');
        }
        $serviceId = $this->getParent()->getServiceId();
        $appId = $this->getAppId($apiKey);

        $records = $this->getParent()->fetchAppDeviceMapping($appId);
        foreach ($records as $record) {
            if ($record['device_token'] === $oldToken) {
                NotificationAppDevice::deleteById($record['id']);
                $model = NotificationAppDevice::create([
                    'service_id'   => $serviceId,
                    'app_id'       => $appId,
                    'device_token' => $newToken
                ]);
                break;
            }
        }

        if (!empty($model)) {
            return ResponseFactory::create(['id' => $model->id], null, ServiceResponseInterface::HTTP_OK);
        } else {
            throw new NotFoundException('No existing record found for the old token provided.');
        }
    }

    /**
     * Handles updating device registration.
     *
     * @return \DreamFactory\Core\Utility\ServiceResponse
     */
    protected function handlePATCH()
    {
        return $this->handlePUT();
    }

    /**
     * Handles deleting registered devices.
     *
     * @return array
     */
    protected function handleDELETE()
    {
        $apiKey = $this->getApiKey(true);
        $appId = $this->getAppId($apiKey);
        $serviceId = $this->getParent()->getServiceId();
        NotificationAppDevice::where('service_id', $serviceId)->where('app_id', $appId)->delete();

        return ['success' => true];
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
                'get'    => [
                    'summary'     => 'Retrieves registered device tokens',
                    'description' => 'Retrieves registered device tokens',
                    'operationId' => 'get' . $capitalized . 'RegisteredDevices',
                    'parameters'  => [
                        [
                            'name'        => 'api_key',
                            'schema'      => ['type' => 'string'],
                            'description' => 'DreamFactory application API Key',
                            'in'          => 'query',
                        ],
                    ],
                    'responses'   => [
                        '200' => [
                            'description' => 'Success',
                            'content'     => [
                                'application/json' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'resource' => [
                                                'type'  => 'array',
                                                'items' => [
                                                    'type'       => 'object',
                                                    'properties' => [
                                                        'id'                  => ['type' => 'integer'],
                                                        'service_id'          => ['type' => 'integer'],
                                                        'app_id'              => ['type' => 'integer'],
                                                        'device_token'        => ['type' => 'string'],
                                                        'created_date'        => ['type' => 'string'],
                                                        'last_modified_date'  => ['type' => 'string'],
                                                        'created_by_id'       => ['type' => 'integer'],
                                                        'last_modified_by_id' => ['type' => 'integer'],
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
                'post'   => [
                    'summary'     => 'Register device token',
                    'description' => 'Registers device token with an application using API Key.',
                    'operationId' => 'register' . $capitalized . 'DeviceToken',
                    'parameters'  => [
                        [
                            'name'        => 'api_key',
                            'schema'      => ['type' => 'string'],
                            'description' => 'DreamFactory application API Key. Only required when no API Key is provided in request headers.',
                            'in'          => 'query',
                        ],
                    ],
                    'requestBody' => [
                        'description' => 'Device token to register',
                        'content'     => [
                            'application/json' => [
                                'schema' => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'device_token' => [
                                            'type'        => 'string',
                                            'description' => 'Target device token to register. '
                                        ]
                                    ],
                                ],
                            ],
                        ],
                        'required'    => true
                    ],
                    'responses'   => [
                        '200' => [
                            'description' => 'Success',
                            'content'     => [
                                'application/json' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'id' => ['type' => 'integer']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
                'put'    => [
                    'summary'     => 'Update/Replace device token',
                    'description' => 'Update/Replace existing device token.',
                    'operationId' => 'update' . $capitalized . 'DeviceToken',
                    'parameters'  => [
                        [
                            'name'        => 'api_key',
                            'schema'      => ['type' => 'string'],
                            'description' => 'DreamFactory application API Key. Only required when no API Key is provided in request headers.',
                            'in'          => 'query',
                        ],
                    ],
                    'requestBody' => [
                        'description' => 'Device Token to update/replace.',
                        'content'     => [
                            'application/json' => [
                                'schema' => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'old_token' => [
                                            'type'        => 'string',
                                            'description' => 'Old device token to replace. '
                                        ],
                                        'new_token' => [
                                            'type'        => 'string',
                                            'description' => 'New device token to replace with. '
                                        ]
                                    ],
                                ],
                            ],
                        ],
                        'required'    => true
                    ],
                    'responses'   => [
                        '200' => [
                            'description' => 'Success',
                            'content'     => [
                                'application/json' => [
                                    'schema' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'id' => ['type' => 'integer']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
                'delete' => [
                    'summary'     => 'Delete device token',
                    'description' => 'Deletes all device tokens registered by an API Key (App).',
                    'operationId' => 'delete' . $capitalized . 'DeviceToken',
                    'parameters'  => [
                        [
                            'name'        => 'api_key',
                            'schema'      => ['type' => 'string'],
                            'description' => 'DreamFactory application API Key. Only required when no API Key is provided in request headers.',
                            'in'          => 'query',
                        ]
                    ],
                    'responses'   => [
                        '200' => [
                            'description' => 'Success',
                            'content'     => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/responses/Success']
                                ]
                            ]
                        ],
                    ],
                ]
            ]
        ];

        return $base;
    }
}