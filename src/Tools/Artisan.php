<?php

namespace Laravel\InstallerTools\Tools;

class Artisan
{
    public function __construct(protected string $directory) {}

    public function run(string $command): void
    {
        $process = proc_open(
            'php artisan '.$command,
            [STDIN, STDOUT, STDERR],
            $pipes,
            $this->directory,
        );

        proc_close($process);
    }

    public function migrate(): void
    {
        $this->run('migrate');
    }

    public function vendorPublish(string $provider): void
    {
        $this->run("vendor:publish --provider=\"{$provider}\"");
    }
}
