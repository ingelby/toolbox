<?php

namespace ingelby\toolbox\helpers;

/**
 * Class Security
 * @package ingelby\toolbox\helpers
 */
class Security
{
    const ENCRYPTED_FILE_EXTENSION = '.enc';

    /**
     * @param string $source
     * @param string $password
     * @return string destination file
     * @throws \RuntimeException
     */
    public static function encryptFile($source, $password)
    {

        $destination = $source . static::ENCRYPTED_FILE_EXTENSION;

        $handle = fopen($source, 'rb');
        $contents = fread($handle, filesize($source));
        fclose($handle);

        $iv = substr(md5("\x1B\x3C\x58" . $password, true), 0, 8);
        $key = substr(md5("\x2D\xFC\xD8" . $password, true) . md5("\x2D\xFC\xD9" . $password, true), 0, 24);
        $opts = array('iv' => $iv, 'key' => $key);
        if (false === $fp = fopen($destination, 'wb')) {
            throw new \RuntimeException('Could not open file for writing.');
        }
        stream_filter_append($fp, 'mcrypt.tripledes', STREAM_FILTER_WRITE, $opts);
        if (false === fwrite($fp, $contents)) {
            throw new \RuntimeException('Could not write to file.');
        }
        fclose($fp);
        unlink($source);

        return $destination;
    }

    public static function decryptFile($source)
    {
        //Todo
    }

    /**
     * @param string $source
     * @param string $password
     * @return bool|string
     */
    public static function peakDecryptFile($source, $password)
    {
        $iv = substr(md5("\x1B\x3C\x58".$password, true), 0, 8);
        $key = substr(md5("\x2D\xFC\xD8".$password, true) .
            md5("\x2D\xFC\xD9".$password, true), 0, 24);
        $opts = array('iv'=>$iv, 'key'=>$key);
        $fp = fopen($source, 'rb');

        //Return the file as is if does not have correct file extension
        if (static::ENCRYPTED_FILE_EXTENSION === '.'.pathinfo($source, PATHINFO_EXTENSION)) {
            stream_filter_append($fp, 'mdecrypt.tripledes', STREAM_FILTER_READ, $opts);
        }

        return stream_get_contents($fp);
    }
}