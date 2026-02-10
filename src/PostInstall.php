<?php

namespace Laravel\InstallerTools;

use Laravel\InstallerTools\Tools\Artisan;
use Laravel\InstallerTools\Tools\Composer;
use Laravel\InstallerTools\Tools\Config;
use Laravel\InstallerTools\Tools\Env;
use Laravel\InstallerTools\Tools\File;
use Laravel\InstallerTools\Tools\Npm;
use Laravel\InstallerTools\Tools\Php\PhpFile;

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

    public function withAnswers(string $path): static
    {
        $this->answers = json_decode(file_get_contents($path), true);

        return $this;
    }

    public function answer(string $key, mixed $default = null): mixed
    {
        return $this->answers[$key] ?? $default;
    }

    public function selected(string $key, string $value, callable $callback, ?callable $otherwise = null): static
    {
        if (in_array($value, $this->answer($key, []))) {
            $callback($this);
        } elseif ($otherwise) {
            $otherwise($this);
        }

        return $this;
    }

    public function confirmed(string $key, callable $callback, ?callable $otherwise = null): static
    {
        if ($this->answer($key, false)) {
            $callback($this);
        } elseif ($otherwise) {
            $otherwise($this);
        }

        return $this;
    }

    public function answered(string $key, mixed $value, callable $callback, ?callable $otherwise = null): static
    {
        if ($this->answer($key) === $value) {
            $callback($this);
        } elseif ($otherwise) {
            $otherwise($this);
        }

        return $this;
    }

    // File operations --------------------------------------------------------

    public function copy(string $from, string $to): static
    {
        $this->file()->copy($from, $to);

        return $this;
    }

    public function delete(string ...$paths): static
    {
        $this->file()->delete(...$paths);

        return $this;
    }

    public function replaceInFile(string $file, string $search, string $replace): static
    {
        $this->file()->replaceInFile($file, $search, $replace);

        return $this;
    }

    public function deleteLinesContaining(string $file, string $content): static
    {
        $this->file()->deleteLinesContaining($file, $content);

        return $this;
    }

    public function appendToFile(string $file, string $content): static
    {
        $this->file()->appendToFile($file, $content);

        return $this;
    }

    public function uncomment(string $file, string $search): static
    {
        $this->file()->uncomment($file, $search);

        return $this;
    }

    public function stripBlock(string $file, string $tag): static
    {
        $this->file()->stripBlock($file, $tag);

        return $this;
    }

    public function removeBlock(string $file, string $tag): static
    {
        $this->file()->removeBlock($file, $tag);

        return $this;
    }

    public function publish(string $from): static
    {
        $this->file()->publish($from);

        return $this;
    }

    // Package and command tools ----------------------------------------------

    public function composer(string $command, string ...$packages): static
    {
        $composer = new Composer($this->directory);

        match ($command) {
            'require' => $composer->require(...$packages),
            'require-dev' => $composer->requireDev(...$packages),
            'remove' => $composer->remove(...$packages),
        };

        return $this;
    }

    public function npm(string $command, string ...$packages): static
    {
        $npm = new Npm($this->directory);

        match ($command) {
            'install' => $npm->install(...$packages),
            'install-dev' => $npm->installDev(...$packages),
            'remove' => $npm->remove(...$packages),
        };

        return $this;
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

    public function php(string $path): PhpFile
    {
        return new PhpFile($this->path($path));
    }

    // Helpers ----------------------------------------------------------------

    protected function file(): File
    {
        return new File($this->directory);
    }
}
