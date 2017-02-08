<?php
namespace DreamFactory\Core\Notification\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use Sly\NotificationPusher\Adapter\Apns;
use Sly\NotificationPusher\PushManager;
use DreamFactory\Core\Notification\Resources\APNSPush as PushResource;
use DreamFactory\Core\Notification\Resources\Register as RegisterResource;

class APNService extends BaseService
{
    /** @var null | string */
    protected $certificateFile = null;

    /** @type array Service Resources */
    protected static $resources = [
        PushResource::RESOURCE_NAME     => [
            'name'       => PushResource::RESOURCE_NAME,
            'class_name' => PushResource::class,
            'label'      => 'Push'
        ],
        RegisterResource::RESOURCE_NAME => [
            'name'       => RegisterResource::RESOURCE_NAME,
            'class_name' => RegisterResource::class,
            'label'      => 'Register'
        ],
    ];

    /** {@inheritdoc} */
    public function getResources($onlyHandlers = false)
    {
        return ($onlyHandlers) ? static::$resources : array_values(static::$resources);
    }

    /** {@inheritdoc} */
    protected function setPusher($config)
    {
        $environment = array_get($config, 'environment');
        if (empty($environment)) {
            throw new InternalServerErrorException(
                'Missing application environment. Please check service configuration for Environment config.'
            );
        }
        $certificate = array_get($config, 'certificate');
        if (empty($certificate)) {
            throw new InternalServerErrorException(
                'Certificate not found. ' .
                'Please check service configuration for Certificate config.'
            );
        }

        $this->createCertificateFile($certificate);
        $apnsConfig = ['certificate' => $this->certificateFile];
        $passphrase = array_get($config, 'passphrase');
        if (!empty($passphrase)) {
            $apnsConfig['passPhrase'] = $passphrase;
        }
        $this->pushManager = new PushManager($environment);
        $this->pushAdapter = new Apns($apnsConfig);
    }

    /**
     * Constructs the certificate file (in tmp dir) from data stored in DB.
     *
     * @param $certificate
     */
    protected function createCertificateFile($certificate)
    {
        $tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->certificateFile = $tmpDir . 'apns-cert-' . $this->getServiceId() . '.pem';
        file_put_contents($this->certificateFile, $certificate);
    }

    /**
     * Deletes the certificate file.
     *
     * @return bool
     */
    public function deleteCertificateFile()
    {
        @unlink($this->certificateFile);

        return true;
    }
}