<?php

namespace Seier\Resting\Fields;

use Exception;

class IntField extends FieldAbstract
{
    protected $value = 0;

    protected function setMutator($value)
    {
        if (is_int($value)) {
            return $value;
        }

        if (!preg_match('/^[0-9]+$/', $value)) {
            $this->error(new Exception('validation.int'));
        }

        return is_null($value) ? null : (int)$value;
    }

    protected function fieldValidation(): array
    {
        return ['int'];
    }

    public function type(): array
    {
        return [
            'type' => 'integer',
            'format' => 'int64',
        ];
    }
}