<?php
namespace DreamFactory\Core\Notification\Services;

use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use Sly\NotificationPusher\Adapter\AdapterInterface;
use Sly\NotificationPusher\PushManager;

abstract class BaseService extends BaseRestService
{
    /** @var null | PushManager */
    protected $pushManager = null;

    /** @var null | AdapterInterface */
    protected $pushAdapter = null;

    /**
     * BaseService constructor.
     *
     * @param array $settings
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function __construct(array $settings = [])
    {
        parent::__construct($settings);

        $config = array_get($settings, 'config');
        Session::replaceLookups($config, true);

        if (empty($config)) {
            throw new InternalServerErrorException('No service configuration found for notification service.');
        }

        $this->setPusher($config);
    }

    /**
     * Sets the push manager and push adapter to be used in resources.
     *
     * @param $config
     * @throws InternalServerErrorException
     */
    abstract protected function setPusher($config);

    /**
     * Returns the push manager.
     *
     * @return null|\Sly\NotificationPusher\PushManager
     */
    public function getPushManager()
    {
        return $this->pushManager;
    }

    /**
     * Returns the push adapter.
     *
     * @return null|\Sly\NotificationPusher\Adapter\AdapterInterface
     */
    public function getPushAdapter()
    {
        return $this->pushAdapter;
    }
}