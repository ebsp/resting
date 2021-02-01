<?php


namespace Seier\Resting\Support;


trait HasPath
{

    private array $pathComponents = [];

    public function prependPath(string|int|array $components): static
    {
        $components = is_array($components) ? $components : [$components];

        array_unshift($this->pathComponents, ...$components);

        return $this;
    }

    public function getPathComponents(): array
    {
        return $this->pathComponents;
    }

    public function getPath(): string
    {
        return join('.', $this->pathComponents);
    }
}