<?php

namespace Seier\Resting\Validator;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

class Validator
{
    protected $instance;

    public function __construct()
    {
        $filesystem = new Filesystem;

        $loader = new FileLoader($filesystem, dirname(dirname(__FILE__)) . '/lang');
        $factory = new Translator($loader, 'en');
        $this->instance = new Factory($factory);
    }
}
