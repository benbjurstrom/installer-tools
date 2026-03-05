<?php

namespace Laravel\Chisel;

use InvalidArgumentException;
use Laravel\Chisel\Tools\File;

class PendingFiles
{
    /**
     * @param  array<string>  $paths
     */
    public function __construct(
        protected File $file,
        protected array $paths,
    ) {}

    public function delete(): static
    {
        $this->file->delete(...$this->paths);

        return $this;
    }

    public function replace(string $search, string $replace): static
    {
        foreach ($this->paths as $path) {
            $this->file->replace($path, $search, $replace);
        }

        return $this;
    }

    public function removeLinesContaining(string $content): static
    {
        foreach ($this->paths as $path) {
            $this->file->removeLinesContaining($path, $content);
        }

        return $this;
    }

    public function replaceLine(string $search, string $replace): static
    {
        foreach ($this->paths as $path) {
            $this->file->replaceLine($path, $search, $replace);
        }

        return $this;
    }

    public function append(string $content): static
    {
        foreach ($this->paths as $path) {
            $this->file->append($path, $content);
        }

        return $this;
    }

    public function appendAfterLine(string $search, string $content): static
    {
        foreach ($this->paths as $path) {
            $this->file->appendAfterLine($path, $search, $content);
        }

        return $this;
    }

    public function uncomment(string $search): static
    {
        foreach ($this->paths as $path) {
            $this->file->uncomment($path, $search);
        }

        return $this;
    }

    public function removeSection(string $tag): static
    {
        foreach ($this->paths as $path) {
            $this->file->removeSection($path, $tag);
        }

        return $this;
    }

    public function removeSectionMarkers(string $tag): static
    {
        foreach ($this->paths as $path) {
            $this->file->removeSectionMarkers($path, $tag);
        }

        return $this;
    }

    public function copyTo(string $to): static
    {
        $this->file->copy($this->singlePath('copyTo'), $to);

        return $this;
    }

    public function publish(): static
    {
        $this->file->publish($this->singlePath('publish'));

        return $this;
    }

    protected function singlePath(string $method): string
    {
        if (count($this->paths) !== 1) {
            throw new InvalidArgumentException("PendingFiles::{$method}() requires exactly one path.");
        }

        return $this->paths[0];
    }
}
