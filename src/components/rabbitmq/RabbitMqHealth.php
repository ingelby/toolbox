<?php

namespace ingelby\toolbox\components\rabbitmq;

use ingelby\toolbox\components\rabbitmq\exceptions\RabbitMqHeathClientException;
use ingelby\toolbox\components\rabbitmq\exceptions\RabbitMqHeathConfigurationException;
use ingelby\toolbox\components\rabbitmq\mapping\Queue;
use yii\base\Component;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Stream\Stream;
use yii\helpers\Json;


class RabbitMqHealth extends Component
{
    /**
     * @var string
     */
    public $host;

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $password;

    /**
     * @var int
     */
    public $port = 15672;

    /**
     * @var string
     */
    public $vhost = '%2f';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @throws RabbitMqHeathConfigurationException
     */
    public function init()
    {
        parent::init();

        if (!isset(
            $this->host, $this->username, $this->password)
        ) {
            throw new RabbitMqHeathConfigurationException('Missing configuration attribute');
        }

        $this->client = new Client(
            [
                'base_uri' => $this->host . ':' . $this->port,
                'auth'     => [
                    $this->username, $this->password,
                ],
            ]
        );
    }

    /**
     * @return Queue[]
     * @throws RabbitMqHeathClientException
     */
    public function getQueues()
    {
        $uri = '/api/queues';

        $queues = $this->get($uri);

        $mappedQueues = [];
        foreach ($queues as $queue) {
            $mappedQueue = new Queue();
            $mappedQueue->setAttributes($queue);
            $mappedQueues[] = $mappedQueue;
        }

        return $mappedQueues;
    }

    /**
     * @param string $name
     * @param string $vhost
     * @return Queue
     * @throws RabbitMqHeathClientException
     */
    public function getQueue($name, $vhost = null)
    {
        if (null === $vhost) {
            $vhost = $this->vhost;
        }

        $uri = '/api/queues/' . $vhost . '/' . $name;

        $queue =  new Queue();
        $queue->setAttributes($this->get($uri));

        return $queue;
    }

    /**
     * @param $uri
     * @param array $options
     * @return array
     * @throws RabbitMqHeathClientException
     */
    protected function get($uri, array $options = [])
    {
        \Yii::info('Calling: ' . $uri);
        try {
            $response = $this->client->get($uri, $options);

            \Yii::info('Response was successful');

            /** @var Stream $stream */
            $stream = $response->getBody();


            /** @var array $response */
            $response = Json::decode($stream->getContents());

            \Yii::info($response);


            return $response;

        } catch (ClientException $exception) {
            \Yii::error(
                [
                    'message' => $exception->getMessage(),
                    'trace'   => $exception->getTraceAsString(),
                    'line'    => $exception->getLine(),
                    'file'    => $exception->getFile(),
                ]
            );
            throw new RabbitMqHeathClientException($exception->getMessage());
        }
    }
}