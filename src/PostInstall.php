<?php

namespace Laravel\InstallerTools;

use Laravel\InstallerTools\Tools\Artisan;
use Laravel\InstallerTools\Tools\Composer;
use Laravel\InstallerTools\Tools\Config;
use Laravel\InstallerTools\Tools\Env;
use Laravel\InstallerTools\Tools\File;
use Laravel\InstallerTools\Tools\Npm;
use Laravel\InstallerTools\Tools\Php\PhpFile;

use function Laravel\Prompts\confirm as promptConfirm;
use function Laravel\Prompts\multiselect as promptMultiselect;
use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\select as promptSelect;
use function Laravel\Prompts\suggest as promptSuggest;
use function Laravel\Prompts\text as promptText;

/** @phpstan-consistent-constructor */
class PostInstall
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

    public function withAnswers(?string $path): static
    {
        if ($path !== null && file_exists($path)) {
            $this->answers = json_decode(file_get_contents($path), true);
        }

        return $this;
    }

    public function answer(string $key, mixed $default = null): mixed
    {
        return $this->answers[$key] ?? $default;
    }

    // Prompts ----------------------------------------------------------------

    public function text(string $name, string $label, string $placeholder = '', string $default = '', bool|string $required = false, string $hint = ''): static
    {
        $this->answers[$name] ??= promptText($label, $placeholder, $default, $required, hint: $hint);

        return $this;
    }

    public function password(string $name, string $label, string $placeholder = '', bool|string $required = false, string $hint = ''): static
    {
        $this->answers[$name] ??= promptPassword($label, $placeholder, $required, hint: $hint);

        return $this;
    }

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

    public function suggest(string $name, string $label, array|Closure $options, string $placeholder = '', string $default = '', string $hint = ''): static
    {
        $this->answers[$name] ??= promptSuggest($label, $options, $placeholder, $default, hint: $hint);

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

    public function file(string ...$paths): PendingFiles
    {
        return $this->files(...$paths);
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
        $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $this->directory);

        proc_close($process);

        return $this;
    }

    // PHP AST ----------------------------------------------------------------

    public function phpFile(string $path): PhpFile
    {
        return new PhpFile($this->path($path));
    }
}
