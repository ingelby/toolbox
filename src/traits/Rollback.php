<?php


namespace ingelby\toolbox\traits;


use Yii;
use yii\db\ActiveRecord;

trait Rollback
{
    /**
     * @param ActiveRecord[] $models
     */
    protected function rollback(array $models): void
    {
        //Reverse so we dont have to deal with cascade issues
        $models = array_reverse($models);
        Yii::info('Rolling back ' . count($models) . ' items');
        foreach ($models as $model) {
            if (null === $model) {
                continue;
            }
            Yii::info('Rolling back ' . get_class($model));
            try {
                $model->delete();
            } catch (\Throwable $e) {
                Yii::warning('Unable to rollback: ' . $e->getMessage());
            }
        }
    }
}