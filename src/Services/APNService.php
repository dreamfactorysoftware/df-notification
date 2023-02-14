<?php
namespace DreamFactory\Core\Notification\Services;

use Sly\NotificationPusher\Model\Device;
use Sly\NotificationPusher\PushManager;
use DreamFactory\Core\Notification\Adapters\APNAdapter;
use DreamFactory\Core\Notification\Resources\APNSPush as PushResource;
use DreamFactory\Core\Notification\Resources\Register as RegisterResource;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
        ]
    ];

    /** {@inheritdoc} */
    public function getDevice($token)
    {
        return new Device(strtolower($token));
    }

    /** {@inheritdoc} */
    protected function setPusher($config)
    {
        $apnsConfig = [];

        $environment = Arr::get($config, 'environment');
        if (empty($environment)) {
            throw new InternalServerErrorException(
                'Missing application environment. Please check service configuration for Environment config.'
            );
        }

        $certificate = Arr::get($config, 'certificate');
        if (empty($certificate)) {
            throw new InternalServerErrorException(
                'Certificate not found. ' .
                'Please check service configuration for Certificate config.'
            );
        }

        $passphrase = Arr::get($config, 'passphrase');
        if (!empty($passphrase)) {
            $apnsConfig['passPhrase'] = $passphrase;
        }

        $topic = $this->getBundleIdFromCert($certificate);
        if (empty($topic)) {
            throw new InternalServerErrorException('Could not extract bundle identifier');
        }

        $this->createCertificateFile($certificate);
        $apnsConfig['certificate'] = $this->certificateFile;

        $this->pushManager = new PushManager($environment);
        $this->pushAdapter = new APNAdapter($topic, $apnsConfig);
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

    /**
     * Retrieve bundle ID from the certificate content. Return `false` on failure
     */
    protected function getBundleIdFromCert(string $cert): string|false
    {
        $bundleId = "";
        $separator = "\r\n";
        $line = strtok($cert, $separator);

        while ($line !== false) {
            $line = trim(strtok($separator));
            if (str_starts_with($line, 'friendlyName') && Str::contains($line, 'com')) 
            {
                $pieces = explode(' ', $line);
                $bundleId .= array_pop($pieces);
                break;
            }
        }
        
        return $bundleId ?: false;
    }
}