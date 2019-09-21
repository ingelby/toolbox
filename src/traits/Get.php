<?php

namespace ingelby\toolbox\traits;


use common\helpers\LoggingHelper;
use ingelby\toolbox\helpers\HyperCache;
use yii\caching\TagDependency;
use yii\helpers\StringHelper;

trait Get
{
    use CacheNames;

    /**
     * @var array
     * @deprecated
     * @see HyperCache
     */
    protected static $hyperCache = [];

    /**
     * For retrieving the first of an item by a key
     *
     * @param        $value
     * @param string $key
     * @param bool   $failOnNull
     * @return static|null
     * @throws \yii\web\HttpException
     */
    public static function get($value, string $key = 'id', $failOnNull = false)
    {
        $cacheKey = static::getCacheKey() . __FUNCTION__ . $value . $key;

        if (false !== $value = HyperCache::get($cacheKey)) {
            return $value;
        }

        $model = \Yii::$app->cache->getOrSet($cacheKey, function () use ($cacheKey, $key, $value, $failOnNull) {
            \Yii::info('Caching key: ' . $cacheKey);

            $model = static::findOne([$key => $value]);
            if (null === $model && true === $failOnNull) {
                $friendlyTableName = ucwords(str_replace('_', ' ', static::getCacheKey()));
                throw new \yii\web\HttpException(404, $friendlyTableName . ' not found');
            }

            HyperCache::set($cacheKey, $model);

            return $model;
        }, 3600, new TagDependency(['tags' => self::getCacheTag() . $value]));

        HyperCache::set($cacheKey, $model);
        return $model;
    }
}
