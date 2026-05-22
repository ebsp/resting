<?php


namespace Seier\Resting\Formatting;


use Carbon\CarbonInterface;
use Seier\Resting\RestingSettings;
use Seier\Resting\Fields\CarbonGranularity;
use Seier\Resting\Validation\Secondary\Panics;

class CarbonFormatter implements Formatter
{

    use Panics;

    private CarbonGranularity $granularity = CarbonGranularity::Second;
    private ?string $formatOverride = null;

    public function format(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof CarbonInterface) {
            $this->panic();
        }

        $format = $this->formatOverride
            ?? RestingSettings::instance()->carbonFormat($this->granularity);

        return $value->format($format);
    }

    public function withGranularity(CarbonGranularity $granularity): static
    {
        $this->granularity = $granularity;

        return $this;
    }

    public function withFormat(string $format): static
    {
        $this->formatOverride = $format;

        return $this;
    }
}
