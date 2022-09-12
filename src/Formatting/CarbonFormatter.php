<?php

namespace Seier\Resting\Formatting;

use Carbon\Carbon;
use Seier\Resting\Validation\Secondary\Panics;

class CarbonFormatter implements Formatter
{
    use Panics;

    private ?string $format = null;

    public function format(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Carbon) {
            $this->panic();
        }

        $format = $this->format ?? 'Y-m-d H:i:s';

        return $value->format($format);
    }

    public function withFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }
}