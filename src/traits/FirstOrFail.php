<?php

namespace ingelby\toolbox\traits;


use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

trait FirstOrFail
{
    /**
     * @param array $searchCondition
     * @param bool $cache
     * @return ActiveRecord
     * @throws NotFoundHttpException
     */
    public static function firstOrFail($searchCondition, $cache = true)
    {
        if (true === $cache) {
            $model = static::find()->cache(true, new TagDependency(['tags' => static::class]))->where($searchCondition)->one();
        } else {
            $model = static::find()->where($searchCondition)->one();
        }
        if (null !== $model) {
            return $model;
        }

        throw new NotFoundHttpException();
    }
}