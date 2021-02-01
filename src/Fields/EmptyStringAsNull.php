<?php


namespace Seier\Resting\Fields;


trait EmptyStringAsNull
{

    protected bool $emptyStringAsNull = false;

    /**
     * Instructs the field whether or not to convert any empty strings into null instead. When $state is true,
     * the field is also marked nullable.
     *
     * @param bool $state Whether or not the field should convert empty strings to null.
     * @return $this
     */
    public function emptyStringAsNull(bool $state = true): static
    {
        $this->emptyStringAsNull = $state;

        return $this;
    }

    protected function maybeEmptyStringAsNull(mixed $value): mixed
    {
        if ($this->emptyStringAsNull && is_string($value)) {
            return $value === '' ? null : $value;
        }

        return $value;
    }
}