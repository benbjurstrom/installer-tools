<?php

namespace Laravel\InstallerTools\Tools;

class Composer
{
    public function __construct(protected string $directory) {}

    public function require(string ...$packages): void
    {
        $this->run('require', ...$packages);
    }

    public function requireDev(string ...$packages): void
    {
        $this->run('require', '--dev', ...$packages);
    }

    public function remove(string ...$packages): void
    {
        $this->run('remove', ...$packages);
    }

    protected function run(string ...$args): void
    {
        $command = 'composer '.implode(' ', $args);

        $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $this->directory);

        proc_close($process);
    }
}
