<?php

namespace Laravel\InstallerTools;

use Laravel\InstallerTools\Tools\File;

class PendingFiles
{
    /**
     * @param  array<string>  $paths
     */
    public function __construct(
        protected File $file,
        protected array $paths,
    ) {}

    public function delete(): void
    {
        $this->file->delete(...$this->paths);
    }

    public function replace(string $search, string $replace): void
    {
        foreach ($this->paths as $path) {
            $this->file->replaceInFile($path, $search, $replace);
        }
    }

    public function removeLinesContaining(string $content): void
    {
        foreach ($this->paths as $path) {
            $this->file->removeLinesContaining($path, $content);
        }
    }

    public function replaceLine(string $search, string $replace): void
    {
        foreach ($this->paths as $path) {
            $this->file->replaceLineInFile($path, $search, $replace);
        }
    }

    public function append(string $content): void
    {
        foreach ($this->paths as $path) {
            $this->file->appendToFile($path, $content);
        }
    }

    public function appendAfterLine(string $search, string $content): void
    {
        foreach ($this->paths as $path) {
            $this->file->appendAfterLine($path, $search, $content);
        }
    }

    public function uncomment(string $search): void
    {
        foreach ($this->paths as $path) {
            $this->file->uncomment($path, $search);
        }
    }

    public function removeSection(string $tag): void
    {
        foreach ($this->paths as $path) {
            $this->file->removeBlock($path, $tag);
        }
    }

    public function removeSectionMarkers(string $tag): void
    {
        foreach ($this->paths as $path) {
            $this->file->stripBlock($path, $tag);
        }
    }

    public function copyTo(string $to): void
    {
        $this->file->copy($this->paths[0], $to);
    }

    public function publish(): void
    {
        $this->file->publish($this->paths[0]);
    }
}
