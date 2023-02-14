<?php

namespace DreamFactory\Core\Notification\Adapters;

use Sly\NotificationPusher\Adapter\BaseAdapter;
use Sly\NotificationPusher\Collection\DeviceCollection;
use Sly\NotificationPusher\Model\DeviceInterface;
use Sly\NotificationPusher\Model\PushInterface;
use Sly\NotificationPusher\Model\GcmMessage;
use Sly\NotificationPusher\Model\Push;
use sngrl\PhpFirebaseCloudMessaging\Client as ServiceClient;
use sngrl\PhpFirebaseCloudMessaging\Message as ServiceMessage;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use sngrl\PhpFirebaseCloudMessaging\Notification;

/**
 * @uses \Sly\NotificationPusher\Adapter\BaseAdapter
 */
class FCMAdapter extends BaseAdapter
{
    /**
     * @var ServiceClient
     */
    private $openedClient;

    public function push(PushInterface $push)
    {
        $client = $this->getOpenedClient();
        $pushedDevices = new DeviceCollection(); 
        $tokens = array_chunk($push->getDevices()->getTokens(), 100);

        foreach ($tokens as $tokensRange) {
            $message = $this->getServiceMessageFromOrigin($tokensRange, $push);

            try {

                $response = $client->send($message);
                $response = $this->validateResponse($response);
                $responseResults = $this->getResults($response, $tokensRange);

                foreach ($tokensRange as $token) {
                    /** @var DeviceInterface $device */
                    $device = $push->getDevices()->get($token);

                    $tokenResponse = [];
                    if (isset($responseResults[$token]) && is_array($responseResults[$token])) {
                        $tokenResponse = $responseResults[$token];
                    }

                    if ($response && is_array($response)) {
                        $tokenResponse = array_merge(
                            $tokenResponse,
                            array_diff_key($response, ['results' => true])
                        );
                    }

                    $push->addResponse($device, $tokenResponse);
                    $pushedDevices->add($device);

                    $this->response->addOriginalResponse($device, true);
                    $this->response->addParsedResponse($device, $tokenResponse);
                }
            } catch (\Exception $ex) {
                $this->response->addOriginalResponse($device, false);
                \Log::error($ex->getMessage() . ' Failed to send a notification on token: ' . $token);
            }
        }

        return $pushedDevices;
    }

    /**
     * Get opened client.
     *
     * @return ServiceClient
     */
    public function getOpenedClient()
    {
        if (!isset($this->openedClient)) {
            $this->openedClient = new ServiceClient();
            $this->openedClient->setApiKey($this->getParameter('apiKey'));
            $this->openedClient->injectGuzzleHttpClient(new \GuzzleHttp\Client());
        }

        return $this->openedClient;
    }

    /**
     * Get service message from origin.
     *
     * @param array $tokens Tokens
     * @param Push $message Message
     *
     * @return ServiceMessage
     * @throws \InvalidArgumentException
     */
    public function getServiceMessageFromOrigin(array $tokens, Push $push)
    {
        $data = $push->getOptions();
        $data['message'] = $push->getMessage()->getText();

        $serviceMessage = new ServiceMessage();

        foreach($tokens as $token) {
            $serviceMessage->addRecipient(new Device($token));
        }

        if (isset($data['notificationData']) && !empty($data['notificationData'])) {
            foreach($data['notificationData'] as $title => $body) {
                $serviceMessage->setNotification(new Notification($title, $body));
            }
            unset($data['notificationData']);
        }

        if ($push instanceof GcmMessage) {
            foreach($push->getNotificationData() as $title => $body) {
                $serviceMessage->setNotification(new Notification($title, $body));
            }
        }

        $serviceMessage->setData($data);

        $serviceMessage->setCollapseKey($this->getParameter('collapseKey'));
        $serviceMessage->setPriority($this->getParameter('priority', 'normal'));
        $serviceMessage->setDelayWhileIdle($this->getParameter('delayWhileIdle', false));
        $serviceMessage->setTimeToLive($this->getParameter('ttl', 600));

        return $serviceMessage;
    }

    public function supports($token)
    {
        return is_string($token) && $token !== '';
    }

    public function getDefinedParameters()
    {
        return [
            'collapseKey',
            'priority',
            'delayWhileIdle',
            'ttl',
        ];
    }

    public function getDefaultParameters()
    {
        return [];
    }

    public function getRequiredParameters()
    {
        return ['apiKey'];
    }

    protected function validateResponse($response) {
        switch ($response->getStatusCode()) {
            case 500:
                throw new \RuntimeException('500 Internal Server Error');
                break;
            case 503:
                $exceptionMessage = '503 Server Unavailable';
                if ($retry = $response->getHeaders()->get('Retry-After')) {
                    $exceptionMessage .= '; Retry After: '.$retry;
                }
                throw new \RuntimeException($exceptionMessage);
                break;
            case 401:
                throw new \RuntimeException('401 Forbidden; Authentication Error');
                break;
            case 400:
                throw new \RuntimeException('400 Bad Request; invalid message');
                break;
        }

        if (! $response = json_decode($response->getBody(), true)) {
            throw new \RuntimeException('Response body did not contain a valid JSON response');
        }
        return $response;
    }

    /**
     * Correlate Message and Result.
     *
     * @param mixed $response
     * @param array $ids
     * 
     * @return array
     */
    protected function getResults($response, $ids) {
        if (! isset($response['results'])) {
            throw new \InvalidArgumentException('Response did not contain the proper fields');
        }
        $results =  $response['results'];

        while ($id = array_shift($ids)) {
            $results[$id] = array_shift($results);
        }    
        return $results;
    }
}
