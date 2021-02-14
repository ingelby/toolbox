<?php

namespace ingelby\toolbox\helpers;


use Ramsey\Uuid\Uuid;

class SessionGuid
{
    protected static ?string $sessionGuid = null;

    /**
     * @return string
     */
    public static function get(): string
    {
        if (null === static::$sessionGuid) {
            static::$sessionGuid = Uuid::uuid4()->toString();
        }

        return static::$sessionGuid;
    }

    /**
     * @return string
     */
    public static function getShort(): string
    {
        $guid = static::get();

        return substr($guid, -6);
    }
}
