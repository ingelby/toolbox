<?php

namespace ingelby\toolbox\traits;

trait CacheNames
{

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