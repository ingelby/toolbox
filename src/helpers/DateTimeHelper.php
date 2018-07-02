<?php

namespace common\helpers;

use Carbon\Carbon;

class DateTimeHelper
{
    /**
     * @param Carbon $carbon
     * @param Carbon|null $diffDate
     * @param array $disallowedMeasurements
     * @return string
     */
    public static function diffAsFullString(Carbon $carbon, Carbon $diffDate = null, $disallowedMeasurements = [])
    {
        $fullDateString = [];

        $diff = (int) $carbon->diffInYears($diffDate, true);

        if ($diff > 0) {
            $fullDateString['years'] = $diff . ' ' . LanguageHelper::pluralize('year', $diff);
            $carbon->subYears($diff);
        }


        $diff = (int) $carbon->diffInMonths($diffDate, true);

        if ($diff > 0) {
            $fullDateString['months'] = $diff . ' ' . LanguageHelper::pluralize('month', $diff);
            $carbon->subMonths($diff);
        }


        $diff = (int) $carbon->diffInWeeks($diffDate, true);

        if ($diff > 0) {
            $fullDateString['weeks'] = $diff . ' ' . LanguageHelper::pluralize('week', $diff);
            $carbon->subWeeks($diff);
        }


        $diff = (int) $carbon->diffInDays($diffDate, true);

        if ($diff > 0) {
            $fullDateString['days'] = $diff . ' ' . LanguageHelper::pluralize('day', $diff);
            $carbon->subDays($diff);
        }


        $diff = (int) $carbon->diffInHours($diffDate, true);

        if ($diff > 0) {
            $fullDateString['hours'] = $diff . ' ' . LanguageHelper::pluralize('hour', $diff);
            $carbon->subHours($diff);
        }


        $diff = (int) $carbon->diffInMinutes($diffDate, true);

        if ($diff > 0) {
            $fullDateString['minutes'] = $diff . ' ' . LanguageHelper::pluralize('minutes', $diff);
            $carbon->subMinutes($diff);
        }


        $diff = (int) $carbon->diffInSeconds($diffDate, true);

        if ($diff > 0) {
            $fullDateString['seconds'] = $diff . ' ' . LanguageHelper::pluralize('seconds', $diff);
        }

        foreach ($disallowedMeasurements as $disallowedMeasurement) {
            unset($fullDateString[$disallowedMeasurement]);
        }

        return implode(', ', $fullDateString);
    }
}