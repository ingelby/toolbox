<?php


namespace ingelby\toolbox\components;


use OpenCloud\ObjectStore\Resource\DataObject;
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
     *
     * @var string
     */
    public $region;

    /**
     * Project name ie: giffgaff-money
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
     * If to cleanup local images after
     *
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
        $this->client = new Rackspace(
            Rackspace::UK_IDENTITY_ENDPOINT,
            [
                'username' => $this->username,
                'apiKey'   => $this->apiKey,
            ],

            [
                // Guzzle ships with outdated certs
                Rackspace::SSL_CERT_AUTHORITY => 'system',
                Rackspace::CURL_OPTIONS       => [
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                ],
            ]
        );
    }

    public function listDirectory($remoteFolderPath = '/', $limit = 10000, $options = [])
    {
        $objectStoreService = $this->client->objectStoreService(null, $this->region);

        $cdn = $objectStoreService->getContainer($this->projectContainer);

        $objectList = $cdn->objectList(
            [
                'prefix'    => $this->environment . $remoteFolderPath,
                'delimiter' => '/',
                'limit'     => $limit,
            ]
        );

        $directoryContents = [];

        /** @var DataObject $object */
        foreach ($objectList as $object) {
            if (false === $object->getDirectory()) {
                continue;
            }
            $directoryContents[] = $object->getName();
        }

        return $directoryContents;
    }


    /**
     * @param string $remoteFolderPath
     * @param int    $recursiveDepth
     * @param bool   $root
     * @param int    $limit
     * @param array  $options
     * @return array
     */
    public function listDirectoryTree(
        $remoteFolderPath = '/',
        $recursiveDepth = 0,
        $root = true,
        $limit = 10000,
        $options = []
    )
    {
        if (true === $root) {
            $remoteFolderPath = $this->environment . $remoteFolderPath;
        }

        $objectStoreService = $this->client->objectStoreService(null, $this->region);

        $cdn = $objectStoreService->getContainer($this->projectContainer);

        $objectList = $cdn->objectList(
            [
                'prefix'    => $remoteFolderPath,
                'delimiter' => '/',
                'limit'     => $limit,
            ]
        );

        $directoryContents = [];

        /** @var DataObject $object */
        foreach ($objectList as $object) {
            if (false === $object->getDirectory()) {
                continue;
            }
            $directory = substr($object->getName(), strlen($remoteFolderPath));

            if (0 >= $recursiveDepth) {
                $directoryContents[$directory] = null;
            }
            else {
                $path = $remoteFolderPath . $directory;
                $directoryContents[$directory] = $this->listDirectoryTree(
                    $path,
                    $recursiveDepth - 1,
                    false
                );
            }
        }

        return $directoryContents;
    }

    /**
     * @param string $directoryPath
     * @return array
     */
    public function listObjects($directoryPath = '', array $options = [])
    {
        $directoryPath = $this->environment . '/' . $directoryPath;
        $objectStoreService = $this->client->objectStoreService(null, $this->region);

        $cdn = $objectStoreService->getContainer($this->projectContainer);

        $contents = [];

        $defaultOptions = [
            'prefix' => $directoryPath,
            'limit'  => 100,
        ];

        $options = array_merge($defaultOptions, $options);

        $objectList = $cdn->objectList($options);

        /** @var DataObject $object */
        foreach ($objectList as $object) {
            $contents[] = [
                'fileName'      => substr($object->getName(), strlen($directoryPath)),
                'size'          => $object->getContentLength(),
                'publicUrl'     => (string)$object->getPublicUrl(UrlType::SSL),
                'directoryPath' => $object->getName(),
            ];
        }

        return $contents;
    }

    /**
     * @param \yii\web\UploadedFile $image
     * @param bool                  $appendTimestamp
     * @param string                $remoteFolderPath
     * @param string|null           $localFolderPath
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

        $cdn = $objectStoreService->getContainer($this->projectContainer);

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

        $remoteStorageFullPath = $this->environment . DIRECTORY_SEPARATOR . $remoteFolderPath . $imageName;

        /** @noinspection PhpParamsInspection */

        $response = $cdn->uploadObject($remoteStorageFullPath, $handle);

        if ($this->cleanUp) {
            unlink($localStorageFullPath);
        }

        return (string)$response->getPublicUrl(UrlType::SSL);
    }

    /**
     * @param $fullImageUrl (excluding project container)
     * @return bool true if succesful
     */
    public function deleteImage($fullImageUrl)
    {
        $objectStoreService = $this->client->objectStoreService(null, $this->region);

        $cdn = $objectStoreService->getContainer($this->projectContainer);

        $cdn->deleteObject($this->environment . DIRECTORY_SEPARATOR . $fullImageUrl);

        try {
            $cdn->getObject($fullImageUrl);
        } catch (\Exception $e) {
            return true;
        }

        return false;
    }

    /**
     * @param string $localFolderPath
     * @param string $remoteFolderPath
     * @return string publicUrl
     */
    public function uploadImage($localFolderPath, $remoteFolderPath)
    {
        $objectStoreService = $this->client->objectStoreService(null, $this->region);

        $cdn = $objectStoreService->getContainer($this->projectContainer);

        $handle = fopen($localFolderPath, 'rb');

        $remoteStorageFullPath = $this->environment . DIRECTORY_SEPARATOR . $remoteFolderPath;

        /** @noinspection PhpParamsInspection */

        $response = $cdn->uploadObject($remoteStorageFullPath, $handle);

        return (string)$response->getPublicUrl(UrlType::SSL);
    }

}