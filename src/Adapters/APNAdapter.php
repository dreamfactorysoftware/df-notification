<?php

namespace DreamFactory\Core\Notification\Adapters;

use Sly\NotificationPusher\Model\DeviceInterface;
use Sly\NotificationPusher\Model\PushInterface;
use Sly\NotificationPusher\Model\Push;
use Sly\NotificationPusher\Adapter\BaseAdapter;
use Sly\NotificationPusher\Exception\AdapterException;
use Sly\NotificationPusher\Collection\DeviceCollection;
use Apple\ApnPush\Model\Alert;
use Apple\ApnPush\Model\Aps;
use Apple\ApnPush\Model\Payload;
use Apple\ApnPush\Model\Notification;
use Apple\ApnPush\Model\Priority;
use Apple\ApnPush\Model\Sound;
use Apple\ApnPush\Model\Localized;
use Apple\ApnPush\Model\Receiver;
use Apple\ApnPush\Model\PushType;
use Apple\ApnPush\Model\DeviceToken;
use Apple\ApnPush\Model\Expiration;
use Apple\ApnPush\Model\CollapseId;
use Apple\ApnPush\Sender\Builder\HttpProtocol;
use Apple\ApnPush\Sender\Builder\Http20Builder;
use Apple\ApnPush\Sender\Sender as ServiceClient;
use Apple\ApnPush\Protocol\Http\Response as ServiceResponse;
use Apple\ApnPush\Protocol\Http\Authenticator\CertificateAuthenticator;
use Apple\ApnPush\Certificate\Certificate;
use Apple\ApnPush\Exception\SendNotification\SendNotificationException;
use Illuminate\Support\Arr;

class APNAdapter extends BaseAdapter
{

    /**
     * @var ServiceClient
     */
    private $openedClient;

    /**
     * @var HttpProtocol
     */
    private $httpProtocol;

    /**
     * @var string Bundle ID
     */
    private $topic;

    /**
     * {@inheritdoc}
     *
     * @throws AdapterException
     */
    public function __construct(string $topic, array $parameters = [])
    {
        parent::__construct($parameters);

        $this->topic = $topic;
        $cert = $this->getParameter('certificate');

        if (false === file_exists($cert)) {
            throw new AdapterException(sprintf('Certificate %s does not exist', $cert));
        }
    }

    public function push(PushInterface $push)
    {
        $client = $this->getOpenedServiceClient();
        $pushedDevices = new DeviceCollection(); 

        foreach ($push->getDevices() as $device) {
            $message = $this->getServiceMessageFromOrigin($device, $push);
            $deviceToken = new DeviceToken($device->getToken());
            $receiver = new Receiver($deviceToken, $this->topic);
            try {
                /** @var ServiceResponse $response */
                $client->send($receiver, $message);

                $pushedDevices->add($device);
                $this->response->addOriginalResponse($device, true);
            } catch (SendNotificationException $ex) {
                $this->response->addOriginalResponse($device, false);
                \Log::error($ex->getMessage() . ' Failed to send a notification on device token: ' . $device->getToken());
            }
        }

        $this->httpProtocol->closeConnection();
        return $pushedDevices;
    }

    /**
     * @return ServiceClient
     */
    protected function getOpenedServiceClient()
    {
        if (!isset($this->openedClient)) {
            $certificatePath = $this->getParameter('certificate');
            $passPhrase = $this->getParameter('passPhrase', '');

            $certificate = new Certificate($certificatePath, $passPhrase);
            $authenticator = new CertificateAuthenticator($certificate);
            $builder = new Http20Builder($authenticator);
            
            $this->httpProtocol = $builder->buildProtocol();
            $this->openedClient = new ServiceClient($this->httpProtocol);
        }

        return $this->openedClient;
    }

    /**
     * @param DeviceInterface $device Device
     * @param Push $push Message
     *
     * @return Notification
     */
    public function getServiceMessageFromOrigin(DeviceInterface $device, Push $push)
    {
        $messageText = $push->getMessage()->getText();
        $messageOptions = $push->getMessage()->getOptions();
        $threadId = sha1($device->getToken() . $messageText);

        $alert = new Alert($messageText);
        
        if ($bodyLocKey = Arr::get($messageOptions, 'loc-key')) {
            $bodyLocArgs = Arr::get($messageOptions, 'loc-args', []);
            $bodyLocalized = new Localized($bodyLocKey, $bodyLocArgs);
            $alert = $alert->withBodyLocalized($bodyLocalized);
        }
        if ($titleLocKey = Arr::get($messageOptions, 'title-loc-key')) {
            $titleLocArgs = Arr::get($messageOptions, 'title-loc-args', []);
            $titleLocalized = new Localized($titleLocKey, $titleLocArgs);
            $alert = $alert->withLocalizedTitle($titleLocalized);
        }
        if ($title = Arr::get($messageOptions, 'title')) {
            $alert = $alert->withTitle($title);
        }
        if ($subtitleLocKey = Arr::get($messageOptions, 'subtitle-loc-key')) {
            $subtitleLocArgs = Arr::get($messageOptions, 'subtitle-loc-args', []);
            $subtitleLocalized = new Localized($subtitleLocKey, $subtitleLocArgs);
            $alert = $alert->withLocalizedSubtitle($subtitleLocalized);
        }
        if ($subtitle = Arr::get($messageOptions, 'subtitle')) {
            $alert = $alert->withSubtitle($subtitle);
        }
        if ($actionKey = Arr::get($messageOptions, 'action-loc-key')) {
            $actionLocalized = new Localized($actionKey);
            $alert = $alert->withActionLocalized($actionLocalized);
        }
        if ($image = Arr::get($messageOptions, 'launch-image')) {
            $alert = $alert->withLaunchImage($image);
        }

        $aps = (new Aps($alert))
            ->withThreadId($threadId);

        if ($badge = Arr::get($messageOptions, 'badge')) {
            $aps = $aps->withBadge($badge);
        }
        if ($sound = Arr::get($messageOptions, 'sound')) {
            $volume = Arr::get($messageOptions, 'volume');
            $critical = Arr::get($messageOptions, 'critical', false);
            $sound = new Sound($sound, $volume, $critical);
            $aps = $aps->withSound($sound);
        }
        if ($contentAvailable = Arr::get($messageOptions, 'content-available')) {
            $aps = $aps->withContentAvailable($contentAvailable);
        }
        if ($category = Arr::get($messageOptions, 'category')) {
            $aps = $aps->withCategory($category);
        }
        if ($mutableContent = Arr::get($messageOptions, 'mutable-content')) {
            $aps = $aps->withMutableContent($mutableContent);
        }

        $payload = new Payload($aps);

        if ($custom = Arr::get($messageOptions, 'custom')) {
            $payload = $payload->withCustomData('custom', $custom);
        }

        $notification = (new Notification($payload))
            ->withCollapseId(new CollapseId($device->getToken())) 
            ->withPriority(Priority::immediately())
            ->withPushType(PushType::alert());

        if ($expire = Arr::get($messageOptions, 'expire')) {
            $expiration = new Expiration(date_timestamp_set(date_create(), $expire));
            $notification = $notification->withExpiration($expiration);
        }

        return $notification;
    }

    public function supports($token)
    {
        return ctype_xdigit($token);
    }

    public function getDefinedParameters()
    {
        return [];
    }

    public function getDefaultParameters()
    {
        return ['passPhrase' => null];
    }

    public function getRequiredParameters()
    {
        return ['certificate'];
    }
}
