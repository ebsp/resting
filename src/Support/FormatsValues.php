<?php


namespace Seier\Resting\Support;


trait FormatsValues
{

    protected function formatArray(array $array): string
    {
        return ValueFormatter::instance()->formatArray($array);
    }

    protected function format($value, bool $showType = true): string
    {
        return ValueFormatter::instance()->format(
            $value,
            $showType
        );
    }
}