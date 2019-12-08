<?php

namespace ingelby\toolbox\traits;

trait CacheNames
{
    /**
     * Should be used when you want to be careful of subclasses when storing in class.
     * @return string
     */
    public static function getSafeCacheKey(): string
    {
        return static::class . '_' . static::tableName();
    }

    /**
     * @return string
     */
    public static function getCacheKey(): string
    {
        return static::tableName();
    }

    /**
     * @return string
     */
    public static function getCacheTag(): string
    {
        return static::tableName() . '-TAG_';
    }
}