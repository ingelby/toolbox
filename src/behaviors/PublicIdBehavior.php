<?php

namespace ingelby\toolbox\behaviors;

use yii\base\InvalidCallException;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;

class PublicIdBehavior extends AttributeBehavior
{
    /**
     * @var string the attribute that will receive public Id value
     * Set this property to false if you do not want to record the creation time.
     */
    public $publicIdAttribute = 'publicId';
    /**
     * @var bool if it should be human understandable.
     * Set this property to false if you want to it to be a guid.
     */
    public $humanFriendly = true;

    /**
     * @var ActiveRecord
     */
    public $model;

    /**
     * @inheritdoc
     *
     */
    public $value;


    public $attributes;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->attributes)) {
            $this->attributes = [
                BaseActiveRecord::EVENT_BEFORE_INSERT => [$this->publicIdAttribute],
            ];
        }
    }

    protected function humanify($string)
    {
        if (false === $this->humanFriendly) {
            return $string;
        }

        return strtoupper(substr(str_replace(['_', '-'], '', $string), 0 , 6));
    }

    /**
     * @inheritdoc
     *
     * In case, when the [[value]] is `null`, the result of the PHP function [time()](http://php.net/manual/en/function.time.php)
     * will be used as value.
     */
    protected function getValue($event)
    {
        $value = $this->humanify(\Yii::$app->security->generateRandomString());

        while (null !== $this->model::findOne([$this->publicIdAttribute => $value])) {
            $value = $this->humanify(\Yii::$app->security->generateRandomString());
        }

        if ($this->value === null) {
            return $value;
        }
        return parent::getValue($event);
    }

    /**
     * Updates a public id attribute to the current timestamp.
     *
     * @param string $attribute the name of the attribute to update.
     * @throws InvalidCallException if owner is a new record (since version 2.0.6).
     */
    public function touch($attribute)
    {
        /* @var $owner BaseActiveRecord */
        $owner = $this->owner;
        if ($owner->getIsNewRecord()) {
            throw new InvalidCallException('Updating the timestamp is not possible on a new record.');
        }
        $owner->updateAttributes(array_fill_keys((array) $attribute, $this->getValue(null)));
    }

}