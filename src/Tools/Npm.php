<?php

namespace Laravel\Chisel\Tools;

use Illuminate\Process\Factory;

class Npm
{
    public function __construct(protected string $directory) {}

    public function remove(string ...$packages): void
    {
        (new Factory)
            ->path($this->directory)
            ->forever()
            ->run(['npm', 'remove', ...$packages])
            ->throw();
    }
}
