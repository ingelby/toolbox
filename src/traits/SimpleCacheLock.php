<?php

namespace ingelby\toolbox\traits;

trait SimpleCacheLock
{
    /**
     * @param string $lockName
     * @return bool
     */
    public static function hasLock($lockName)
    {
        \Yii::info('Lock check: ' . $lockName);
        return false !== \Yii::$app->cache->get($lockName);
    }

    /**
     * @param string $lockName
     * @param int    $interval
     * @return bool
     */
    public static function waitForLock($lockName, $interval = 500000)
    {
        \Yii::info('Waiting for lock: ' . $lockName);
        if (!static::hasLock($lockName)) {
            \Yii::info('No lock for: ' . $lockName);
            return static::lock($lockName);
        }
        \Yii::info('Lock in place, sleeping for: ' . $interval);

        usleep($interval);

        return static::waitForLock($lockName, $interval);
    }

    /**
     * @param string $lockName
     * @return bool
     */
    public static function lock($lockName)
    {
        \Yii::info('Locking: ' . $lockName);
        return \Yii::$app->cache->set($lockName, 'locked', 30);
    }

    /**
     * @param string $lockName
     * @return bool
     */
    public static function unlock($lockName)
    {
        \Yii::info('Unlocking: ' . $lockName);
        return \Yii::$app->cache->delete($lockName);
    }
}
