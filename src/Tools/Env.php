<?php

namespace Laravel\Chisel\Tools;

use Winter\LaravelConfigWriter\EnvFile;

class Env
{
    public function __construct(protected string $directory) {}

    public function set(string $key, string $value): void
    {
        EnvFile::open($this->directory.'/.env')
            ->set($key, $value)
            ->write();
    }
}
