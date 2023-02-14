<?php

namespace DreamFactory\Core\Notification;

use DreamFactory\Core\Enums\LicenseLevel;
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
    public function boot()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df) {
            $df->addType(
                new ServiceType([
                    'name'                  => 'apns',
                    'label'                 => 'Apple Push Notification',
                    'description'           => 'Apple Push Notification Service Provider.',
                    'group'                 => ServiceTypeGroups::NOTIFICATION,
                    'subscription_required' => LicenseLevel::SILVER,
                    'config_handler'        => ApnsConfig::class,
                    'factory'               => function ($config) {
                        return new APNService($config);
                    },
                ])
            );

            $df->addType(
                new ServiceType([
                    'name'                  => 'gcm',
                    'label'                 => 'FCM Push Notification',
                    'description'           => 'FCM Push Notification Service Provider.',
                    'group'                 => ServiceTypeGroups::NOTIFICATION,
                    'subscription_required' => LicenseLevel::SILVER,
                    'config_handler'        => GcmConfig::class,
                    'factory'               => function ($config) {
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