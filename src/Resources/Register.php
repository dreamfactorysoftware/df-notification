<?php

namespace DreamFactory\Core\Notification\Resources;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\ConflictResourceException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Notification\Models\NotificationAppDevice;
use DreamFactory\Core\Utility\ResourcesWrapper;
use DreamFactory\Core\Utility\ResponseFactory;
use DreamFactory\Core\Contracts\ServiceResponseInterface;

class Register extends BaseResource
{
    /** Resource name constant */
    const RESOURCE_NAME = 'register';

    /**
     * @return array
     */
    protected function handleGET()
    {
        $apiKey = $this->getApiKey();
        if(empty($apiKey)){
            $serviceId = $this->getParent()->getServiceId();
            $records = NotificationAppDevice::where('service_id', $serviceId)->get();

            return ResourcesWrapper::wrapResources($records->toArray());
        } else {
            $appId = $this->getAppId($apiKey);

            return ResourcesWrapper::wrapResources($this->fetchAppDeviceMapping($appId));
        }
    }

    /**
     * @return \DreamFactory\Core\Utility\ServiceResponse
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \DreamFactory\Core\Exceptions\ConflictResourceException
     */
    protected function handlePOST()
    {
        $apiKey = $this->getApiKey(true);
        $deviceToken = $this->request->getPayloadData('device_token');
        if(empty($deviceToken)){
            throw new BadRequestException('No Device Token found. Please provide a valid Device Token.');
        }
        $deviceToken = strtolower($deviceToken);
        $serviceId = $this->getParent()->getServiceId();
        $appId = $this->getAppId($apiKey);
        $existingTokens = $this->getDeviceTokenByApiKey($apiKey);
        if(!in_array($deviceToken, $existingTokens)) {
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
     * @return \DreamFactory\Core\Utility\ServiceResponse
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \DreamFactory\Core\Exceptions\NotFoundException
     */
    protected function handlePUT()
    {
        $apiKey = $this->getApiKey(true);
        $oldToken = $this->request->getPayloadData('old_token');
        if(empty($oldToken)){
            throw new BadRequestException('Old/Existing token not found. Please provide the old token that you are replacing.');
        }
        $newToken = $this->request->getPayloadData('new_token', $this->request->getPayloadData('device_token'));
        if(empty($newToken)){
            throw new BadRequestException('New token not found. Please provide the new token that you are replacing the old token with.');
        }
        $oldToken = strtolower($oldToken);
        $newToken = strtolower($newToken);
        $serviceId = $this->getParent()->getServiceId();
        $appId = $this->getAppId($apiKey);

        $records = $this->fetchAppDeviceMapping($appId);
        foreach ($records as $record){
            if($record['device_token'] === $oldToken){
                NotificationAppDevice::deleteById($record['id']);
                $model = NotificationAppDevice::create([
                    'service_id'   => $serviceId,
                    'app_id'       => $appId,
                    'device_token' => $newToken
                ]);
                break;
            }
        }

        if(!empty($model)){
            return ResponseFactory::create(['id' => $model->id], null, ServiceResponseInterface::HTTP_OK);
        } else {
            throw new NotFoundException('No existing record found for the old token provided.');
        }
    }

    /**
     * @return \DreamFactory\Core\Utility\ServiceResponse
     */
    protected function handlePATCH()
    {
        return $this->handlePUT();
    }

    /**
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
}