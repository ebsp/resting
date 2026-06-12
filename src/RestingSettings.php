<?php

namespace Seier\Resting;

use Seier\Resting\Fields\CarbonGranularity;

class RestingSettings
{
    private static ?RestingSettings $instance = null;

    public bool $useImmutableCarbon = false;
    public bool $removeEmptyArrays = false;
    public bool $removeNulls = false;
    public int $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;

    private array $carbonFormats = [
        'date' => 'Y-m-d',
        'hour' => 'Y-m-d H',
        'minute' => 'Y-m-d H:i',
        'second' => 'Y-m-d H:i:s',
    ];

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

    public function carbonFormat(CarbonGranularity $granularity): string
    {
        return $this->carbonFormats[$granularity->value];
    }

    public function setCarbonFormat(CarbonGranularity $granularity, string $format): static
    {
        $this->carbonFormats[$granularity->value] = $format;

        return $this;
    }

    public function setJsonOptions(int $options): static
    {
        $this->jsonOptions = $options;

        return $this;
    }
}
