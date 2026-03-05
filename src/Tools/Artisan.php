<?php

namespace Laravel\Chisel\Tools;

use Illuminate\Process\Factory;

class Artisan
{
    public function __construct(protected string $directory) {}

    public function run(string $command): void
    {
        (new Factory)
            ->path($this->directory)
            ->forever()
            ->run('php artisan '.$command, function (string $type, string $buffer): void {
                if ($type === 'err') {
                    fwrite(STDERR, $buffer);

                    return;
                }

                echo $buffer;
            })
            ->throw();
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
