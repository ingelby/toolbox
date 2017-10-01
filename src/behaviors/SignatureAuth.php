<?php

namespace ingelby\toolbox\behaviors;

use ingelby\toolbox\constants\HttpStatus;
use yii\base\Behavior;
use yii\rest\Controller;
use yii\web\HeaderCollection;
use yii\web\HttpException;

/**
 * Class SignatureAuth
 * @package ingelby\toolbox\behaviors
 */
class SignatureAuth extends Behavior
{
    const SALT = 'salt';
    const SIGNATURE = 'signature';
    const API_PUBLIC_KEY = 'api-key';

    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
        ];
    }


    public function beforeAction($event)
    {
        $headers = \Yii::$app->request->headers;

        $this->validateHeaders($headers);

        $publicKey = $headers->get(static::API_PUBLIC_KEY);

        $this->validatePublicKey($publicKey);

        $salt = $headers->get(static::SALT);
        $signature = $headers->get(static::SIGNATURE);
        $this->authenticate($salt, $signature, $publicKey);
    }

    /**
     * @param HeaderCollection $headers
     * @return bool
     * @throws HttpException
     */
    protected function validateHeaders(HeaderCollection $headers)
    {
        if (!$headers->has(static::SALT)) {
            throw new HttpException(HttpStatus::UNAUTHORIZED, 'Missing ' . static::SALT . ' header');
        }
        if (!$headers->has(static::SIGNATURE)) {
            throw new HttpException(HttpStatus::UNAUTHORIZED, 'Missing ' . static::SIGNATURE . ' header');
        }
        if (!$headers->has(static::API_PUBLIC_KEY)) {
            throw new HttpException(HttpStatus::UNAUTHORIZED, 'Missing ' . static::API_PUBLIC_KEY . ' header');
        }

        return true;
    }

    /**
     * @param $publicKey
     * @return bool
     * @throws HttpException
     */
    protected function validatePublicKey($publicKey)
    {
        if (array_key_exists($publicKey, \Yii::$app->params['api-keys'])) {
            return true;
        }
        throw new HttpException(HttpStatus::UNAUTHORIZED, 'Invalid public key');
    }

    /**
     * @param $salt
     * @param $signature
     * @param $publicKey
     * @return bool
     * @throws HttpException
     */
    protected function authenticate($salt, $signature, $publicKey)
    {
        $privateKey = \Yii::$app->params['api-keys'][$publicKey];

        $hash = hash_hmac('sha256', $salt, $privateKey, true);
        $hashBase64 = base64_encode($hash);

        if ($hashBase64 === $signature) {
            return true;
        }

        throw new HttpException(HttpStatus::UNAUTHORIZED, 'Signature is wrong');
    }
}