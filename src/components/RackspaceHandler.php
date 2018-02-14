<?php

use OpenCloud\Rackspace;
use yii\web\UploadedFile;
use OpenCloud\ObjectStore\Constants\UrlType;

namespace ingelby\toolbox\behaviors;


class RackspaceHandler extends Component
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
     * @param null $localStorageFullPath
     * @return bool|string false on failure, public url on success
     */
    public function storeImage(UploadedFile $image, $appendTimestamp = false, $localStorageFullPath = null)
    {
        $objectStoreService = $this->client->objectStoreService(null, $this->region);

        $bouxCdn = $objectStoreService->getContainer(\Yii::$app->params['cdnServiceName']);

        $imageName = '';

        if ($appendTimestamp) {
            $imageName .= time() . '_';
        }
        $imageName .= urlencode($image->baseName) . '.' . $image->extension;

        if (null === $localStorageFullPath) {
            $localStorageFullPath = (string) '/tmp/' . $this->projectContainer . DIRECTORY_SEPARATOR . $imageName;
        }

        if (false === $image->saveAs($localStorageFullPath, false)) {
            return false;
        }

        $handle = fopen($localStorageFullPath, 'rb');


        /** @noinspection PhpParamsInspection */

        $response = $bouxCdn->uploadObject($this->environment . DIRECTORY_SEPARATOR  . $imageName, $handle);

        return (string) $response->getPublicUrl(UrlType::SSL);
    }

}