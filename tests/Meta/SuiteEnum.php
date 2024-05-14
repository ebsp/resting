<?php

namespace Seier\Resting\Tests\Meta;

enum SuiteEnum: string
{
    case Hearts = 'hearts';
    case Diamonds = 'diamonds';
    case Clubs = 'clubs';
    case Spades = 'spades';

    public function proof(){
        return 1;
    }
}
