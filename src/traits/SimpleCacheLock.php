<?php

namespace ingelby\toolbox\traits;

trait SimpleCacheLock
{
    /**
     * Does the lock name have a lock on it
     * @param string $lockName
     * @return bool returns true if there is a lock
     */
    public static function hasLock($lockName)
    {
        \Yii::info('Lock check: ' . $lockName);
        return false !== \Yii::$app->cache->get($lockName);
    }

    /**
     * @param string $lockName
     * @param int    $interval
     * @param int    $lockTimeout time in seconds
     * @return bool
     */
    public static function waitForLock($lockName, $interval = 500000, $lockTimeout = 30)
    {
        \Yii::info('Waiting for lock: ' . $lockName);
        if (!static::hasLock($lockName)) {
            \Yii::info('No lock for: ' . $lockName);
            return static::lock($lockName, $lockTimeout);
        }
        \Yii::info('Lock in place, sleeping for: ' . $interval);

        usleep($interval);

        return static::waitForLock($lockName, $interval);
    }

    /**
     * @param string $lockName
     * @param int    $lockTimeout time in seconds
     * @return bool
     */
    public static function lock($lockName, $lockTimeout = 30)
    {
        \Yii::info('Locking: ' . $lockName);
        return \Yii::$app->cache->set($lockName, 'locked', $lockTimeout);
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
