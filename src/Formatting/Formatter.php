<?php


namespace Seier\Resting\Formatting;


interface Formatter
{

    public function format(mixed $value): mixed;
}