<?php

namespace ingelby\toolbox\services\inguzzle;

use ingelby\toolbox\helpers\LoggingHelper;
use ingelby\toolbox\services\inguzzle\exceptionsInguzzleClientException;
use ingelby\toolbox\services\inguzzle\exceptionsInguzzleInternalServerException;
use ingelby\toolbox\services\inguzzle\exceptionsInguzzleServerException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Yii;
use yii\helpers\Json;

class InguzzleHandler
{
    /**
     * @var string[]
     */
    private $supportedMethods =
        [
            'post',
            'patch',
            'get',
            'delete',
            'put',
            'head',
        ];

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $uriPrefix;

    /**
     * @var string
     */
    protected $loggingCategory = 'application';

    /**
     * @var null|callable
     */
    protected $clientErrorResponseCallback = null;

    /**
     * @var null|callable
     */
    protected $serverErrorResponseCallback = null;

    /**
     * BaseRequest constructor.
     *
     * @param string        $baseUrl
     * @param string        $uriPrefix
     * @param callable|null $clientErrorResponseCallback
     * @param callable|null $serverErrorResponseCallback
     */
    public function __construct(
        $baseUrl,
        $uriPrefix = '',
        callable $clientErrorResponseCallback = null,
        callable $serverErrorResponseCallback = null
    )
    {
        $this->uriPrefix = $uriPrefix;
        $this->client = new Client(
            [
                'base_uri' => $baseUrl,
            ]
        );
        $this->clientErrorResponseCallback = $clientErrorResponseCallback;
        $this->serverErrorResponseCallback = $serverErrorResponseCallback;
    }

    /**
     * @return string
     */
    public function getLoggingCategory(): string
    {
        return $this->loggingCategory;
    }

    /**
     * @param string $loggingCategory
     */
    public function setLoggingCategory(string $loggingCategory): void
    {
        $this->loggingCategory = $loggingCategory;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $options
     * @return array
     * @throws InguzzleClientException
     * @throws InguzzleInternalServerException
     * @throws InguzzleServerException
     */
    protected function request($method, $uri, array $options): ?array
    {
        $method = strtolower($method);

        if (!in_array($method, $this->supportedMethods)) {
            throw new InguzzleInternalServerException('Unsupported method, ' . $method);
        }

        Yii::info('Sending request to: ' . $uri, $this->loggingCategory);

        try {
            /** @var ResponseInterface $response */
            $response = $this->client->$method(
                $this->uriPrefix . $uri,
                $options
            );


            $response = $response->getBody()->getContents();
            Yii::info('Request was successful, response not in log', $this->loggingCategory);

            return Json::decode($response);
        } catch (ClientException $exception) {
            LoggingHelper::LogException($exception, $this->loggingCategory);

            /** @var Stream $stream */
            /** @noinspection NullPointerExceptionInspection */
            $stream = $exception->getResponse()->getBody();
            $errorResponse = $stream->getContents();
            /** @noinspection NullPointerExceptionInspection */
            $statusCode = $exception->getResponse()->getStatusCode();

            $message = $errorResponse;

            if (is_callable($this->clientErrorResponseCallback)) {
                $callable = $this->clientErrorResponseCallback;
                $message = $callable($errorResponse);
            }

            throw new InguzzleClientException($statusCode, $message, 0, $exception);
        } catch (ServerException $exception) {
            LoggingHelper::LogException($exception, $this->loggingCategory);

            /** @var Stream $stream */
            /** @noinspection NullPointerExceptionInspection */
            $stream = $exception->getResponse()->getBody();
            $errorResponse = $stream->getContents();
            /** @noinspection NullPointerExceptionInspection */
            $statusCode = $exception->getResponse()->getStatusCode();

            $message = $errorResponse;

            if (is_callable($this->serverErrorResponseCallback)) {
                $callable = $this->serverErrorResponseCallback;
                $message = $callable($errorResponse);
            }

            Yii::error(
                'Server error calling: ' . $uri . ' response: ' . $errorResponse . ' message: ' . $message,
                $this->loggingCategory
            );

            throw new InguzzleServerException($statusCode, $message, 0, $exception);
        } catch (Throwable $exception) {
            LoggingHelper::LogException($exception, $this->loggingCategory);

            Yii::error(
                'Internal server error calling: ' . $uri . ' response: ' . $exception->getMessage(),
                $this->loggingCategory
            );

            throw new InguzzleInternalServerException(500, $exception->getMessage(), 500, $exception);
        }
    }


    /**
     * @param string $uri
     * @param array  $queryParameters
     * @param array  $additionalHeaders
     * @return array
     * @throws InguzzleClientException
     * @throws InguzzleInternalServerException
     * @throws InguzzleServerException
     */
    public function get($uri, array $queryParameters = [], array $additionalHeaders = [])
    {
        Yii::info('Sending request to: ' . $uri, $this->loggingCategory);
        Yii::info($queryParameters, $this->loggingCategory);

        $options = [
            'query'   => $queryParameters,
            'headers' =>
                array_merge(
                    [
                        'content-type' => 'application/json',
                        'Accept'       => 'application/json',
                    ],
                    $additionalHeaders
                ),
        ];


        return $this->request('get', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array  $body
     * @param array  $queryParameters
     * @param array  $additionalHeaders
     * @return array
     * @throws InguzzleClientException
     * @throws InguzzleInternalServerException
     * @throws InguzzleServerException
     */
    public function post($uri, array $body = [], array $queryParameters = [], array $additionalHeaders = [])
    {
        Yii::info('Sending request to: ' . $uri, $this->loggingCategory);
        Yii::info($queryParameters, $this->loggingCategory);

        $payload = Json::encode($body);

        Yii::info($payload, $this->loggingCategory);

        $options = [
            'query'   => $queryParameters,
            'body'    => $payload,
            'headers' =>
                array_merge(
                    [
                        'content-type' => 'application/json',
                        'Accept'       => 'application/json',
                    ],
                    $additionalHeaders
                ),
        ];


        return $this->request('post', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array  $queryParameters
     * @param array  $additionalHeaders
     * @return array
     * @throws InguzzleClientException
     * @throws InguzzleInternalServerException
     * @throws InguzzleServerException
     */
    public function delete($uri, array $queryParameters = [], array $additionalHeaders = [])
    {
        Yii::info('Sending request to: ' . $uri, $this->loggingCategory);
        Yii::info($queryParameters, $this->loggingCategory);

        $options = [
            'query'   => $queryParameters,
            'headers' =>
                array_merge(
                    [
                        'content-type' => 'application/json',
                        'Accept'       => 'application/json',
                    ],
                    $additionalHeaders
                ),
        ];


        return $this->request('delete', $uri, $options);
    }

    /**
     * @return callable|null
     */
    public function getClientErrorResponseCallback(): ?callable
    {
        return $this->clientErrorResponseCallback;
    }

    /**
     * @param callable|null $clientErrorResponseCallback
     */
    public function setClientErrorResponseCallback(?callable $clientErrorResponseCallback): void
    {
        $this->clientErrorResponseCallback = $clientErrorResponseCallback;
    }

    /**
     * @return callable|null
     */
    public function getServerErrorResponseCallback(): ?callable
    {
        return $this->serverErrorResponseCallback;
    }

    /**
     * @param callable|null $serverErrorResponseCallback
     */
    public function setServerErrorResponseCallback(?callable $serverErrorResponseCallback): void
    {
        $this->serverErrorResponseCallback = $serverErrorResponseCallback;
    }
}
