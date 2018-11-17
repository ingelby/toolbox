<?php

namespace ingelby\toolbox\traits;


use yii\caching\TagDependency;
use yii\db\ActiveRecord;

trait FirstOrCreate
{
    protected static $lastErrors = [];

    /**
     * @return array
     */
    public static function getLastErrors()
    {
        return static::$lastErrors;
    }

    /**
     * @param mixed|array $searchCondition
     * @param array|null $createParameters
     * @param bool $cache
     * @return ActiveRecord|bool false if unable to create
     */
    public static function firstOrCreate($searchCondition, array $createParameters = null, $cache = true)
    {
        if (true === $cache) {
            $model = static::find()->cache(true, new TagDependency(['tags' => static::class]))->where($searchCondition)->one();
        } else {
            $model = static::find()->where($searchCondition)->one();
        }
        if (null !== $model) {
            return $model;
        }

        if (null === $createParameters) {
            //This is good for simple things like ['foo' => 'bar']
            $createParameters = $searchCondition;
        }
        $model = new static($createParameters);
        if (!$model->save()) {
            \Yii::error('Unable to save model, see next line for error');
            \Yii::error($model->getErrors());
            static::$lastErrors = $model->getErrors();
            return false;

        }
        TagDependency::invalidate(\Yii::$app->cache, static::class);

        return $model;
    }
}