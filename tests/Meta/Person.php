<?php


namespace Seier\Resting\Tests\Meta;


class Person
{

	public string $name;
	public int $age;

	public static function from(string $name, int $age): static
	{
		$person = new static();
		$person->name = $name;
		$person->age = $age;

		return $person;
	}
}