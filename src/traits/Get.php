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
     * @param bool   $honorClassInheritance Set to false if you dont care about the subclass being returned
     * @return static|null
     * @throws \yii\web\HttpException
     */
    public static function get($value, string $key = 'id', $failOnNull = false, $honorClassInheritance = true)
    {
        //This is important as it will take into account class inheritance
        $cacheKey = static::getSafeCacheKey() . __FUNCTION__ . $value . $key;

        if (false === $honorClassInheritance) {
            $cacheKey = static::getCacheKey() . __FUNCTION__ . $value . $key;
        }

        if (false !== $cacheValue = HyperCache::get($cacheKey)) {
            if (null === $cacheValue && true === $failOnNull) {
                $friendlyTableName = ucwords(str_replace('_', ' ', static::getCacheKey()));
                throw new \yii\web\HttpException(404, $friendlyTableName . ' not found');
            }
            return $cacheValue;
        }
        $model = \Yii::$app->cache->getOrSet($cacheKey, function () use ($cacheKey, $key, $value, $failOnNull) {
            \Yii::info('Caching key: ' . $cacheKey);
            $model = static::findOne([$key => $value]);
            if (null === $model && true === $failOnNull) {
                $friendlyTableName = ucwords(str_replace('_', ' ', static::getCacheKey()));
                throw new \yii\web\HttpException(404, $friendlyTableName . ' not found');
            }

            HyperCache::set($cacheKey, $model, self::getCacheTag() . $value);

            return $model;
        }, 3600, new TagDependency(['tags' => self::getCacheTag() . $value]));

        HyperCache::set($cacheKey, $model, self::getCacheTag() . $value);

        return $model;
    }
}
