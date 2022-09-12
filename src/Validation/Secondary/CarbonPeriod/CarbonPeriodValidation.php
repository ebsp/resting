<?php

namespace Seier\Resting\Validation\Secondary\CarbonPeriod;

use Carbon\CarbonInterval;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

trait CarbonPeriodValidation
{
    protected abstract function getSupportsSecondaryValidation(): SupportsSecondaryValidation;

    public function minHours(int $hours): static
    {
        return $this->minInterval(CarbonInterval::hours($hours));
    }

    public function maxHours(int $hours): static
    {
        return $this->maxInterval(CarbonInterval::hours($hours));
    }

    public function minDays(int $days): static
    {
        return $this->minInterval(CarbonInterval::days($days));
    }

    public function maxDays(int $days): static
    {
        return $this->maxInterval(CarbonInterval::days($days));
    }

    public function minWeeks(int $weeks): static
    {
        return $this->minInterval(CarbonInterval::weeks($weeks));
    }

    public function maxWeeks(int $weeks): static
    {
        return $this->maxInterval(CarbonInterval::weeks($weeks));
    }

    public function minInterval(CarbonInterval $min): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new CarbonPeriodMinDurationValidator($min)
        );

        return $this;
    }

    public function maxInterval(CarbonInterval $max, bool $allowWithoutEnd = true): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new CarbonPeriodMaxDurationValidator($max, $allowWithoutEnd)
        );

        return $this;
    }
}