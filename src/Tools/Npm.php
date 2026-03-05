<?php

namespace Laravel\Chisel\Tools;

use Illuminate\Process\Factory;

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
        (new Factory)
            ->path($this->directory)
            ->forever()
            ->run(['npm', ...$args], function (string $type, string $buffer): void {
                if ($type === 'err') {
                    fwrite(STDERR, $buffer);

                    return;
                }

                echo $buffer;
            })
            ->throw();
    }
}
