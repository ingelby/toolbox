<?php


namespace ingelby\toolbox\components;


use GuzzleHttp\Psr7\Stream;
use OpenStack\OpenStack;
use yii\web\UploadedFile;

class RackspaceV2Handler extends \yii\base\Component
{
    const REGION_LONDON = 'LON';

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $apiKey;

    /**
     * Will default to LON
     *
     * @var string
     */
    public $region;

    /**
     * Project name
     *
     * @var string
     */
    public $projectContainer;

    /**
     * The environment, live, staging... etc
     *
     * @var string
     */
    public $environment;

    /**
     * @var string
     */
    public $identityUrl = 'https://lon.identity.api.rackspacecloud.com/v2.0/';

    /**
     * If to cleanup local images after
     *
     * @var bool
     */
    public $cleanUp = true;

    /**
     * @var OpenStack
     */
    protected ?OpenStack $openstack = null;

    public function init()
    {
        parent::init();
        if (null === $this->username || null === $this->apiKey) {
            throw new \RuntimeException('Please provide a username and apiKey for the rackspace component');
        }
        if (null === $this->projectContainer) {
            throw new \RuntimeException('Project container must be set');
        }
        if (null === $this->environment) {
            throw new \RuntimeException('Environment must be set');
        }
        if (null === $this->region) {
            $this->region = static::REGION_LONDON;
        }

        $this->openstack = new OpenStack([
            'authUrl' => $this->identityUrl,
            'region'  => $this->region,
            'user'    => [
                'id'       => $this->username,
                'password' => $this->apiKey,
            ],
            'scope'   => ['project' => ['id' => $this->projectContainer]]
        ]);

    }


    /**
     * @param UploadedFile $uploadedFile
     * @param bool                  $appendTimestamp
     * @param string                $remoteFolderPath
     * @param string|null           $localFolderPath
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
            'name'    => $remoteStorageFullPath,
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
