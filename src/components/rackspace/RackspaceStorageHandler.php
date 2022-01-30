<?php

namespace ingelby\toolbox\components\rackspace;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use ingelby\toolbox\components\rackspace\exceptions\RackspaceHandlerAuthenticationBaseException;
use ingelby\toolbox\components\rackspace\exceptions\RackspaceHandlerUploadException;
use ingelby\toolbox\constants\HttpStatus;
use ingelby\toolbox\enums\RequestMethod;
use OpenCloud\Rackspace;

class RackspaceStorageHandler extends \yii\base\Component
{
    public const REGION_LON = 'LON';
    public const PROJECT_OBJECT_STORE = 'object-store';

    protected const URL_TYPE_PUBLIC_URL = 'publicURL';

    public ?string $username = null;
    public ?string $apiKey = null;
    public ?string $projectContainer = null;
    public ?string $environment = null;
    public ?string $region = self::REGION_LON;

    public string $identityEndpoint = Rackspace::UK_IDENTITY_ENDPOINT;

    protected Client $customClient;
    protected string $authToken;
    protected string $cloudFilesEndpointUrl;
    protected string $cloudFilesCdnEndpointUrl;
    protected ?string $containerCdnUrlHttps = null;

    private array $corsHeaders = [
        'X-Container-Meta-Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods'                 => '*',
        'Access-Control-Allow-Origin'                  => '*',
        'Access-Control-Expose-Headers'                => 'Origin, X-Requested-With, Content-Type, Accept, Authorization',
        'Access-Control-Request-Headers'               => 'Origin, X-Requested-With, Content-Type, Accept, Authorization',
    ];

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

        $authClient = new Client([
            'headers' => [
                'Content-type' => 'application/json',
            ],
        ]);

        try {
            $response = $authClient->request(
                RequestMethod::POST->value,
                $this->identityEndpoint . 'tokens',
                [
                    'json' => [
                        'auth' => [
                            'RAX-KSKEY:apiKeyCredentials' => [
                                'username' => $this->username,
                                'apiKey'   => $this->apiKey,
                            ],
                        ],
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            throw new RackspaceHandlerAuthenticationBaseException(
                'Unable to get tokens from rackspace: ' . $e->getMessage(),
                0,
                $e
            );
        }

        if (HttpStatus::OK !== $response->getStatusCode()) {
            throw new RackspaceHandlerAuthenticationBaseException(
                $response->getStatusCode() . ' returned from rackspace authentication API'
            );
        }

        $authResponseObject = json_decode($response->getBody());

        $authToken = $authResponseObject?->access?->token?->id;

        if (null === $authToken) {
            throw new RackspaceHandlerAuthenticationBaseException('No authToken in auth response');
        }

        $this->authToken = $authToken;


        $service_catalog = $authResponseObject->access->serviceCatalog;
        foreach ($service_catalog as $catalog) {
            if ('cloudFilesCDN' === $catalog->name) {
                foreach ($catalog->endpoints as $endpoint) {
                    if (static::REGION_LON === $endpoint->region) {
                        $this->cloudFilesCdnEndpointUrl = $endpoint->publicURL;
                        break;
                    }
                }
            }

            if ('cloudFiles' === $catalog->name) {
                foreach ($catalog->endpoints as $endpoint) {
                    if (static::REGION_LON === $endpoint->region) {
                        $this->cloudFilesEndpointUrl = $endpoint->publicURL;
                    }
                }
            }

        }

        $this->customClient = new Client(
            [
                'headers'     => [
                    'Content-type' => 'application/json',
                    'X-Auth-Token' => $this->authToken,
                ],
                'http_errors' => false,
            ]
        );
    }

    /**
     * @param string $fullLocalFileName
     * @param string $remoteFilePathName
     * @return string
     * @throws RackspaceHandlerUploadException
     */
    public function uploadObject(
        string $fullLocalFileName,
        string $remoteFilePathName,
    ): string
    {

        if (false === $fp = fopen($fullLocalFileName, 'rb+')) {
            throw new RackspaceHandlerUploadException('Error reading: ' . $fullLocalFileName);
        }
        $content_type = mime_content_type($fp);

        $fileName = basename($remoteFilePathName);
        try {
            $object = [
                'name'    => $fileName,
                'body'    => $fp,
                'headers' => $this->corsHeaders,
            ];
            if ($content_type !== false) {
                $object['headers']['Content-Type'] = $content_type;
            }

            $uri = substr($remoteFilePathName, 0, -strlen($fileName));
            $remoteFilePath = $this->projectContainer . DIRECTORY_SEPARATOR . $this->environment . $uri;

            $fullRemoteFilePath = $remoteFilePath . $fileName;
            $fullUrl = $this->cloudFilesEndpointUrl . '/' . $fullRemoteFilePath;

            $this->customClient->put($fullUrl, $object);
            unlink($fullLocalFileName);
            return $this->getHttpsUrlForObject($fullRemoteFilePath);

        } catch (GuzzleException $e) {
            throw new RackspaceHandlerUploadException(
                'Unable to upload: ' . $remoteFilePathName . ' reason: ' . $e->getMessage(),
                0,
                $e
            );
        } catch (\Throwable $e) {
            throw new RackspaceHandlerUploadException(
                'Unknown issue when trying to upload: ' . $remoteFilePathName . ' reason: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * @return void
     * @throws RackspaceHandlerUploadException
     */
    private function initialiseContainerCdnUrls(): void
    {
        try {
            $response = $this->customClient->head($this->cloudFilesCdnEndpointUrl . '/' . $this->projectContainer);
        } catch (GuzzleException $e) {
            throw new RackspaceHandlerUploadException(
                'Unable to get cdn https base url, reason: ' . $e->getMessage(),
                0,
                $e
            );
        }

        // if the container is not CDN-enabled, these URLs will be null
        $this->containerCdnUrlHttps = $response->getHeader('X-Cdn-Ssl-Uri')[0];
    }

    /**
     * @param string $remoteFullPath
     * @return string
     * @throws RackspaceHandlerUploadException
     */
    public function getHttpsUrlForObject(string $remoteFullPath): string
    {
        if (null === $this->containerCdnUrlHttps) {
            $this->initialiseContainerCdnUrls();
        }

        $filename = basename($remoteFullPath);
        //Remove filename
        $uri = substr($remoteFullPath, 0, -strlen($filename));
        //Remove project container
        $uri = substr($uri, strlen($this->projectContainer));
        return $this->containerCdnUrlHttps .$uri . $this->encodeURIComponent($filename);
    }

    /**
     * @param string $string
     * @return string
     * @note this may need to change at some point...
     */
    private function encodeURIComponent(string $string): string
    {
        return $string;
    }

}
