<?php

namespace Laravel\InstallerTools\Tools;

use Winter\LaravelConfigWriter\ArrayFile;

class Config
{
    public function __construct(protected string $directory) {}

    public function set(string $file, string|array $key, mixed $value = null): void
    {
        $config = ArrayFile::open($this->directory.'/'.$file);

        if (is_array($key)) {
            $config->set($key);
        } else {
            $config->set($key, $value);
        }

        $config->write();
    }
}
