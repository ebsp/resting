<?php


namespace Seier\Resting\Validation\Errors;


interface ValidationError
{
    public function getMessage(): string;

    public function getPathComponents(): array;

    public function getPath(): string;

    public function prependPath(string|int|array $components): static;
}