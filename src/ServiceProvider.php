<?php
namespace DreamFactory\Core\Notification;

use DreamFactory\Core\Components\ServiceDocBuilder;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Notification\Handlers\Events\PushEventHandler;
use DreamFactory\Core\Notification\Models\ApnsConfig;
use DreamFactory\Core\Notification\Models\GcmConfig;
use DreamFactory\Core\Notification\Services\APNService;
use DreamFactory\Core\Notification\Services\GCMService;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use Event;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    use ServiceDocBuilder;

    public function boot()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df){
            $df->addType(
                new ServiceType([
                    'name'            => 'apns',
                    'label'           => 'Apple Push Notification',
                    'description'     => 'Apple Push Notification Service Provider.',
                    'group'           => ServiceTypeGroups::NOTIFICATION,
                    'config_handler'  => ApnsConfig::class,
                    'default_api_doc' => function ($service){
                        return $this->buildServiceDoc($service->id, APNService::getApiDocInfo($service));
                    },
                    'factory'         => function ($config){
                        return new APNService($config);
                    },
                ])
            );

            $df->addType(
                new ServiceType([
                    'name'            => 'gcm',
                    'label'           => 'GCM Push Notification',
                    'description'     => 'GCM Push Notification Service Provider.',
                    'group'           => ServiceTypeGroups::NOTIFICATION,
                    'config_handler'  => GcmConfig::class,
                    'default_api_doc' => function ($service){
                        return $this->buildServiceDoc($service->id, GCMService::getApiDocInfo($service));
                    },
                    'factory'         => function ($config){
                        return new GCMService($config);
                    },
                ])
            );
        });

        // add migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Event::subscribe(new PushEventHandler());
    }
}