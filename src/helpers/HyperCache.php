<?php

namespace ingelby\toolbox\helpers;

use Yii;

class HyperCache
{
    /**
     * @var array
     */
    protected static $hyperCache = [];

    /**
     * @var int How many items can be in the hypercache
     */
    protected static $maxHyperCacheLength = 50;

    /**
     * @var int When $maxHyperCacheLength is reached what is the limit of items that need to be removed from the list
     * to make space
     */
    protected static $minimumThrowawayLength = 0;

    /**
     * @param string $cacheKey
     * @return mixed returns false on not found
     */
    public static function get($cacheKey)
    {
        if (\array_key_exists($cacheKey, static::$hyperCache)) {
            $hits = static::$hyperCache[$cacheKey]['hits']++;
            Yii::debug('Item "' . $cacheKey . '" got from hyper cache, hits: ' . $hits);
            return static::$hyperCache[$cacheKey]['value'];
        }

        Yii::debug('Item "' . $cacheKey . '" not found in hyper cache');

        return false;
    }

    /**
     * @param $cacheKey
     * @param $value
     * @return bool
     */
    protected static function store($cacheKey, $value): bool
    {
        Yii::debug('Storing "' . $cacheKey . '" in hyper cache');
        static::$hyperCache[$cacheKey]['value'] = $value;
        static::$hyperCache[$cacheKey]['hits'] = 0;

        return true;
    }

    /**
     * @param string $cacheKey
     */
    public static function delete($cacheKey)
    {
        unset(static::$hyperCache[$cacheKey]);
    }

    /**
     * @param string $cacheKey
     * @param mixed  $value
     * @return bool
     */
    public static function set($cacheKey, $value): bool
    {
        $hyperCacheSize = \count(static::$hyperCache);
        Yii::debug('Setting "' . $cacheKey . '" in hyper cache, current length: ' . $hyperCacheSize);

        if ($hyperCacheSize < static::$maxHyperCacheLength) {
            return static::store($cacheKey, $value);
        }

        Yii::debug('Hyper cache size limit reached');

        uasort(
            static::$hyperCache,
            static function ($itemA, $itemB) {
                if ($itemA['hits'] < $itemB['hits']) {
                    return 1;
                }
                if ($itemA['hits'] === $itemB['hits']) {
                    return 0;
                }

                return -1;
            }
        );

        $lastElement = array_pop(static::$hyperCache);

        Yii::debug('Last element, hits: ' . $lastElement['hits'] . ' new hyper cache length: ' . \count(static::$hyperCache));

        return static::store($cacheKey, $value);
    }
}