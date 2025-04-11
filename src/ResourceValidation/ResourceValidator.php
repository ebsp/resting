<?php

namespace Seier\Resting\ResourceValidation;

interface ResourceValidator
{
    public function description(): string;

    public function validate(): array;
}