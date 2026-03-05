<?php

namespace Laravel\Chisel;

use Illuminate\Process\Factory;
use Laravel\Chisel\Tools\Artisan;
use Laravel\Chisel\Tools\Composer;
use Laravel\Chisel\Tools\Config;
use Laravel\Chisel\Tools\Env;
use Laravel\Chisel\Tools\File;
use Laravel\Chisel\Tools\Npm;
use Laravel\Chisel\Tools\Php\PhpFile;

use function Laravel\Prompts\confirm as promptConfirm;
use function Laravel\Prompts\multiselect as promptMultiselect;
use function Laravel\Prompts\select as promptSelect;

/** @phpstan-consistent-constructor */
class Chisel
{
    protected array $answers = [];

    protected function __construct(protected string $directory) {}

    public static function in(string $directory): static
    {
        return new static($directory);
    }

    public function path(string $path = ''): string
    {
        return $path !== '' && $path !== '0' ? $this->directory.'/'.$path : $this->directory;
    }

    // Answers ----------------------------------------------------------------

    public function withAnswers(?string $json): static
    {
        if ($json !== null) {
            $this->answers = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }

        return $this;
    }

    // Prompts ----------------------------------------------------------------

    public function confirm(string $name, string $label, bool $default = true, string $hint = ''): static
    {
        $this->answers[$name] ??= promptConfirm($label, $default, hint: $hint);

        return $this;
    }

    public function select(string $name, string $label, array $options, int|string|null $default = null, string $hint = ''): static
    {
        $this->answers[$name] ??= promptSelect($label, $options, $default, hint: $hint);

        return $this;
    }

    public function multiselect(string $name, string $label, array $options, array $default = [], string $hint = '', bool|string $required = false): static
    {
        $this->answers[$name] ??= promptMultiselect($label, $options, $default, required: $required, hint: $hint);

        return $this;
    }

    // Branching --------------------------------------------------------------

    public function selected(string $key, string $value, ?callable $then = null, ?callable $else = null): static
    {
        if (in_array($value, $this->answer($key, []))) {
            if ($then) {
                $then($this);
            }
        } elseif ($else) {
            $else($this);
        }

        return $this;
    }

    public function confirmed(string $key, ?callable $then = null, ?callable $else = null): static
    {
        if ($this->answer($key, false)) {
            if ($then) {
                $then($this);
            }
        } elseif ($else) {
            $else($this);
        }

        return $this;
    }

    public function answered(string $key, mixed $value, ?callable $then = null, ?callable $else = null): static
    {
        if ($this->answer($key) === $value) {
            if ($then) {
                $then($this);
            }
        } elseif ($else) {
            $else($this);
        }

        return $this;
    }

    // File operations --------------------------------------------------------

    public function files(string ...$paths): PendingFiles
    {
        return new PendingFiles(new File($this->directory), $paths);
    }

    public function file(string $path): PendingFiles
    {
        return $this->files($path);
    }

    // Package and command tools ----------------------------------------------

    public function composer(): Composer
    {
        return new Composer($this->directory);
    }

    public function npm(): Npm
    {
        return new Npm($this->directory);
    }

    public function artisan(string $command): static
    {
        (new Artisan($this->directory))->run($command);

        return $this;
    }

    public function env(string $key, string $value): static
    {
        (new Env($this->directory))->set($key, $value);

        return $this;
    }

    public function config(string $file, string|array $key, mixed $value = null): static
    {
        (new Config($this->directory))->set($file, $key, $value);

        return $this;
    }

    public function run(string $command): static
    {
        (new Factory)
            ->path($this->directory)
            ->forever()
            ->run($command, function (string $type, string $buffer): void {
                if ($type === 'err') {
                    fwrite(STDERR, $buffer);

                    return;
                }

                echo $buffer;
            })
            ->throw();

        return $this;
    }

    // PHP AST ----------------------------------------------------------------

    public function phpFile(string $path): PhpFile
    {
        return new PhpFile($this->path($path));
    }

    private function answer(string $key, mixed $default = null): mixed
    {
        return $this->answers[$key] ?? $default;
    }
}
