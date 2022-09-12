<?php

namespace Seier\Resting\Support;

class ValueFormatter
{
    public static function instance(): static
    {
        return new static();
    }

    public function format($value, bool $showType = true): string
    {
        if (is_array($value) && !$this->isAssociativeArray($value)) {
            $content = array_map(fn($element) => $this->format($element), $value);
            $joined = join(', ', $content);
            return $showType ? "array [$joined]" : "[$joined]";
        }

        if (is_object($value) || (is_array($value) && $this->isAssociativeArray($value))) {
            return "object";
        }

        if (is_bool($value)) {
            $formatted = $value ? 'true' : 'false';
            return $showType ? "bool ($formatted)" : $formatted;
        }

        if (is_numeric($value)) {
            $formatted = (string)$value;
            return $showType ? "number ($formatted)" : $formatted;
        }

        if (is_string($value)) {
            return $showType ? "string ('$value')" : "'$value'";
        }

        if (is_null($value)) {
            return "null";
        }

        return (string)$value;
    }

    public function formatArray(array $array): string
    {
        $elements = join(', ', array_map(function (mixed $element) {
            return $this->format($element);
        }, $array));

        return "[$elements]";
    }

    protected function isAssociativeArray(array $arr): bool
    {
        if ([] === $arr) {
            return false;
        }

        foreach ($arr as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }
}