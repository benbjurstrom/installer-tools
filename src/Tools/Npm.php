<?php

namespace Laravel\InstallerTools\Tools;

class Npm
{
    public function __construct(protected string $directory) {}

    public function install(string ...$packages): void
    {
        $this->run('install', ...$packages);
    }

    public function installDev(string ...$packages): void
    {
        $this->run('install', '--save-dev', ...$packages);
    }

    public function remove(string ...$packages): void
    {
        $this->run('remove', ...$packages);
    }

    protected function run(string ...$args): void
    {
        $command = 'npm '.implode(' ', $args);

        $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $this->directory);

        proc_close($process);
    }
}
