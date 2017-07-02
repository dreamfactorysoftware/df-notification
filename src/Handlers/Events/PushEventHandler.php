<?php
namespace DreamFactory\Core\Notification\Handlers\Events;

use DreamFactory\Core\Events\ServiceAssignedEvent;
use DreamFactory\Core\Notification\Services\BaseService as PushServices;
use Illuminate\Contracts\Events\Dispatcher;
use Log;

class PushEventHandler
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param  Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen(
            [
                ServiceAssignedEvent::class,
            ],
            static::class . '@handleSubEvent'
        );
    }

    /**
     * @param ServiceAssignedEvent $event
     */
    public function handleSubEvent($event)
    {
        $service = $event->getService();
        if($service instanceof PushServices){
            if($service->isActive()){
                try {
                    $record = $event->getData();
                    $request = array_get($event->makeData(), 'request');
                    $apiKey = array_get(
                        $request,
                        'headers.x-dreamfactory-api-key',
                        array_get($request, 'parameters.api_key')
                    );
                    $message = array_get($record, 'data', $event->name);
                    $service->pushByApiKey($message, $apiKey);
                    Log::debug('Sent push notification on [' . $event->name . '] event.');
                } catch (\Exception $e){
                    Log::error(
                        'Failed to send push notification on [' . $event->name . '] event. ' . $e->getMessage()
                    );
                }
            }
        }
    }
}