<?php

namespace Seier\Resting;

class RestingSettings
{
    private static ?RestingSettings $instance = null;

    public bool $useImmutableCarbon = false;
    public bool $removeEmptyArrays = false;
    public bool $removeNulls = false;

    private function __construct()
    {
    }

    public static function instance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public static function reset(): void
    {
        static::$instance = null;
    }
}
