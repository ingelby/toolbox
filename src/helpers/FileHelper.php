<?php

namespace ingelby\toolbox\helpers;

use DirectoryIterator;

class FileHelper
{
    /**
     * Recursively removes directories
     * @param string $dir
     */
    public static function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir, null);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (filetype($dir . '/' . $object) === 'dir') {
                        static::rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    /**
     * @param $dir
     * @return bool
     */
    public static function is_dir_empty($dir)
    {
        foreach (new DirectoryIterator($dir) as $fileInfo) {
            if ($fileInfo->isDot()) continue;

            return false;
        }

        return true;
    }


    /**
     * Converts bytes into human readable file size.
     *
     * @param int $bytes
     * @param string $separator
     * @return string human readable file size (2,87 Мb)
     */
    public static function humanFileSize($bytes, $separator = ',')
    {
        $result = '0 B';
        $arBytes = [
            0 => [
                'unit'  => 'Tb',
                'value' => 1024 ** 4,
            ],
            1 => [
                'unit'  => 'Gb',
                'value' => 1024 ** 3,
            ],
            2 => [
                'unit'  => 'Mb',
                'value' => 1024 ** 2,
            ],
            3 => [
                'unit'  => 'Kb',
                'value' => 1024,
            ],
            4 => [
                'unit'  => 'b',
                'value' => 1,
            ],
        ];

        foreach ($arBytes as $arItem) {
            if ($bytes >= $arItem['value']) {
                $result = $bytes / $arItem['value'];
                $result = str_replace('.', $separator, (string)round($result, 2)) . ' ' . $arItem['unit'];
                break;
            }
        }

        return $result;
    }

}