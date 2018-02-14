<?php


namespace ingelby\toolbox\components;


use OpenCloud\Rackspace;
use \yii\web\UploadedFile;
use OpenCloud\ObjectStore\Constants\UrlType;

class RackspaceHandler extends \yii\base\Component
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
     * @var string
     */
    public $region;

    /**
     * Project name ie: giffgaff-money
     * @var string
     */
    public $projectContainer;

    /**
     * The environment, live, staging... etc
     * @var string
     */
    public $environment;

    /**
     * If to cleanup local images after
     * @var bool
     */
    public $cleanUp = true;

    /**
     * @var Rackspace
     */
    protected $client;

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
        $this->client = new Rackspace(Rackspace::UK_IDENTITY_ENDPOINT, array(
            'username' => $this->username,
            'apiKey'   => $this->apiKey,
        ));
    }

    /**
     * @param \yii\web\UploadedFile $image
     * @param bool $appendTimestamp
     * @param string $remoteFolderPath
     * @param string|null $localFolderPath
     * @return bool|string false on failure, public url on success
     * @internal param null $localStorageFullPath
     */
    public function storeImage(
        \yii\web\UploadedFile $image,
        $appendTimestamp = false,
        $remoteFolderPath = '',
        $localFolderPath = null
    )
    {
        $objectStoreService = $this->client->objectStoreService(null, $this->region);

        $bouxCdn = $objectStoreService->getContainer($this->projectContainer);

        $imageName = '';

        if ($appendTimestamp) {
            $imageName .= time() . '_';
        }
        $imageName .= rawurlencode($image->baseName) . '.' . $image->extension;

        if (null === $localFolderPath) {
            $localFolderPath = '/tmp/' . $this->projectContainer . DIRECTORY_SEPARATOR;
        }

        $localStorageFullPath = $localFolderPath . $imageName;

        if (!@mkdir($localFolderPath, 0777, true) && !is_dir($localFolderPath)) {
            throw new \RuntimeException('Unable to create directory: ' . $localFolderPath);
        }

        if (false === $image->saveAs($localStorageFullPath, true)) {
            return false;
        }

        $handle = fopen($localStorageFullPath, 'rb');

        $remoteStorageFullPath = $this->environment . DIRECTORY_SEPARATOR  . $remoteFolderPath . $imageName;

        /** @noinspection PhpParamsInspection */

        $response = $bouxCdn->uploadObject($remoteStorageFullPath, $handle);

        if ($this->cleanUp) {
            unlink($localStorageFullPath);
        }

        return (string) $response->getPublicUrl(UrlType::SSL);
    }

    /**
     * @param string $localFolderPath
     * @param string $remoteFolderPath
     * @return string publicUrl
     */
    public function uploadImage($localFolderPath, $remoteFolderPath)
    {
        $objectStoreService = $this->client->objectStoreService(null, $this->region);

        $bouxCdn = $objectStoreService->getContainer($this->projectContainer);

        $handle = fopen($localFolderPath, 'rb');

        $remoteStorageFullPath = $this->environment . DIRECTORY_SEPARATOR  . $remoteFolderPath;

        /** @noinspection PhpParamsInspection */

        $response = $bouxCdn->uploadObject($remoteStorageFullPath, $handle);

        return (string) $response->getPublicUrl(UrlType::SSL);
    }

}