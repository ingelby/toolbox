<?php

namespace ingelby\toolbox\helpers;

use yii\helpers\FileHelper;

class BinaryHelper
{
    /**
     * @param string $base64EncodedBinary
     * @return string
     * @throws \RuntimeException
     */
    public static function getBase64AsBinary($base64EncodedBinary)
    {
        $splitData = explode(',', $base64EncodedBinary);
        if (2 !== \count($splitData)) {
            throw new \RuntimeException(
                'Data not in correct format example would be: "data:image/png;base64,AAAFBfj42Pj4"',
                0
            );
        }

        $binary = base64_decode($splitData[1]);
        if (false === $binary) {
            throw new \RuntimeException('Unable to decode binary', 1);
        }

        return $binary;

    }

    /**
     * @param string $base64EncodedBinary
     * @return string
     * @throws \RuntimeException
     */
    public static function getExtension($base64EncodedBinary)
    {
        $mimeTypeSubString = substr($base64EncodedBinary, 0, 250);
        $regex = '/data:(\w+\/\S+);base64/';
        preg_match($regex, $mimeTypeSubString, $matches);
        if (!isset($matches[1])) {
            throw new \RuntimeException(
                'Unable to determine mimeType from data, please ensure the base64 encoded file contains the mime ' .
                'type for example "data:image/png;base64,", substring of mimetype: ' . $mimeTypeSubString,
                2
            );
        }

        $mimeType = $matches[1];

        $extension = FileHelper::getExtensionsByMimeType($mimeType);

        if ([] === $extension) {
            throw new \RuntimeException(
                'Unable to determine file extension for mime type: ' . $mimeType,
                3
            );
        }

        return $extension[0];
    }

}
