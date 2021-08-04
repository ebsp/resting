<?php


namespace Seier\Resting\Support;


trait FormatsValues
{

    protected function format($value, bool $showType = true): string
    {
        return ValueFormatter::instance()->format(
            $value,
            $showType
        );
    }
}