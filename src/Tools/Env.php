<?php

namespace Laravel\InstallerTools\Tools;

class Env
{
    public function __construct(protected string $directory) {}

    public function set(string $key, string $value): void
    {
        $path = $this->directory.'/.env';

        $contents = file_get_contents($path);

        if (preg_match("/^{$key}=.*/m", $contents)) {
            $contents = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $contents);
        } else {
            $contents .= "\n{$key}={$value}";
        }

        file_put_contents($path, $contents);
    }
}
