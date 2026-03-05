<?php

namespace Laravel\Chisel\Tools;

use Illuminate\Process\Factory;

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
        (new Factory)
            ->path($this->directory)
            ->forever()
            ->run(['composer', ...$args], function (string $type, string $buffer): void {
                if ($type === 'err') {
                    fwrite(STDERR, $buffer);

                    return;
                }

                echo $buffer;
            })
            ->throw();
    }
}
