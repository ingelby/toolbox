<?php


namespace ingelby\toolbox\components;


use GuzzleHttp\Psr7\Stream;
use OpenCloud\ObjectStore\Resource\DataObject;
use OpenCloud\Rackspace;
use yii\web\UploadedFile;

class RackspaceStorageHandler extends \yii\base\Component
{
    public const REGION_LON = 'LON';

    protected const URL_TYPE_PUBLIC_URL = 'publicURL';

    public ?string $username = null;
    public ?string $apiKey = null;
    public ?string $projectContainer = null;
    public ?string $environment = null;
    public ?string $region = self::REGION_LON;

    public string $identityEndpoint = Rackspace::UK_IDENTITY_ENDPOINT;

    protected Rackspace $client;

    public function init()
    {
        parent::init();
        if (null === $this->username || null === $this->apiKey) {
            throw new \RuntimeException('Please provide a username and apiKey for the rackspace component');
        }
        if (null === $this->projectContainer) {
            throw new \RuntimeException('projectContainer must be set');
        }
        if (null === $this->environment) {
            throw new \RuntimeException('environment must be set');
        }
        if (null === $this->region) {
            throw new \RuntimeException('region must be set');
        }

        $this->client = new Rackspace(
            $this->identityEndpoint,
            [
                'username' => $this->username,
                'apiKey'   => $this->apiKey,
            ]
        );


    }

    public function uploadObject(
        string $fullLocalFileName,
        string $remoteFileName,
        array $metaData = []
    )
    {
        $service = $this->client->objectStoreService(null, $this->region, static::URL_TYPE_PUBLIC_URL);
        $container = $service->getContainer($this->projectContainer);


        // specify optional metadata
        $metadata = [
            'Author' => 'Camera Obscura',
            'Origin' => 'Glasgow',
        ];

// specify optional HTTP headers
        $httpHeaders = [
            'Content-Type' => 'application/json',
        ];

        $allHeaders = array_merge(DataObject::stockHeaders($metadata), $httpHeaders);

// upload as usual
        $container->uploadObject('example.txt', fopen('/path/to/file.txt', 'r+'), $allHeaders);

    }


    /**
     * @param UploadedFile $uploadedFile
     * @param bool         $appendTimestamp
     * @param string       $remoteFolderPath
     * @param string|null  $localFolderPath
     * @return bool|\OpenStack\ObjectStore\v1\Models\StorageObject
     */
    public function storeUploadedFile(
        UploadedFile $uploadedFile,
                     $appendTimestamp = false,
                     $remoteFolderPath = '',
                     $localFolderPath = null
    )
    {

        $imageName = '';

        if ($appendTimestamp) {
            $imageName .= time() . '_';
        }
        $imageName .= rawurlencode($uploadedFile->baseName) . '.' . $uploadedFile->extension;

        if (null === $localFolderPath) {
            $localFolderPath = '/tmp/' . $this->projectContainer . DIRECTORY_SEPARATOR;
        }

        $localStorageFullPath = $localFolderPath . $imageName;

        if (!@mkdir($localFolderPath, 0777, true) && !is_dir($localFolderPath)) {
            throw new \RuntimeException('Unable to create directory: ' . $localFolderPath);
        }

        if (false === $uploadedFile->saveAs($localStorageFullPath, true)) {
            return false;
        }

        $stream = new Stream(fopen($localStorageFullPath, 'rb'));

        $remoteStorageFullPath = $this->environment . DIRECTORY_SEPARATOR . $remoteFolderPath . $imageName;

        $options = [
            'name'   => $remoteStorageFullPath,
            'stream' => $stream,
        ];

        /** @var \OpenStack\ObjectStore\v1\Models\StorageObject $object */
        $object = $this->openstack->objectStoreV1()
            ->getContainer($this->projectContainer)
            ->createObject($options);


        if ($this->cleanUp) {
            unlink($localStorageFullPath);
        }

        return $object;
    }
}
