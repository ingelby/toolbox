<?php

namespace ingelby\toolbox\components\rabbitmq\mapping;


use yii\base\Model;

class Queue extends Model
{
    public $messages;
    public $consumers;
    public $name;

    public function rules()
    {
        return [
            [
                [
                    'messages',
                    'consumers',
                    'name',
                ],
                'safe',
            ]
        ];
    }

}