<?php

namespace ingelby\toolbox\helpers;

use Carbon\Carbon;
use yii\helpers\Inflector;

class LanguageHelper
{
    /**
     * @param string $word
     * @param int $occurrences
     * @return string
     */
    public static function pluralize($word, $occurrences = 0)
    {
        if (1 === $occurrences) {
            return $word;
        }

        return Inflector::pluralize($word);
    }
}