<?php

namespace Laravel\InstallerTools\Tools;

class File
{
    public function __construct(protected string $directory) {}

    public function copy(string $from, string $to): void
    {
        $toPath = $this->directory.'/'.$to;
        $toDirectory = dirname($toPath);

        if (! is_dir($toDirectory)) {
            mkdir($toDirectory, 0777, true);
        }

        copy($this->directory.'/'.$from, $toPath);
    }

    public function delete(string ...$paths): void
    {
        foreach ($paths as $path) {
            $fullPath = $this->directory.'/'.$path;

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    public function replaceInFile(string $file, string $search, string $replace): void
    {
        $path = $this->directory.'/'.$file;

        file_put_contents(
            $path,
            str_replace($search, $replace, file_get_contents($path)),
        );
    }

    public function deleteLinesContaining(string $file, string $content): void
    {
        $path = $this->directory.'/'.$file;

        $lines = explode("\n", file_get_contents($path));

        $lines = array_filter($lines, fn ($line): bool => ! str_contains((string) $line, $content));

        file_put_contents($path, implode("\n", $lines));
    }

    public function replaceLineInFile(string $file, string $search, string $replace): void
    {
        $path = $this->directory.'/'.$file;

        $lines = explode("\n", file_get_contents($path));

        $lines = array_map(function ($line) use ($search, $replace): string {
            if (str_contains($line, $search)) {
                $indent = strlen($line) - strlen(ltrim($line));

                return str_repeat(' ', $indent).$replace;
            }

            return $line;
        }, $lines);

        file_put_contents($path, implode("\n", $lines));
    }

    public function appendAfterLine(string $file, string $search, string $content): void
    {
        $path = $this->directory.'/'.$file;

        $lines = explode("\n", file_get_contents($path));

        $lines = array_map(function (string $line) use ($search, $content): string {
            if (str_contains($line, $search)) {
                $indent = strlen($line) - strlen(ltrim($line));

                return $line."\n".str_repeat(' ', $indent).$content;
            }

            return $line;
        }, $lines);

        file_put_contents($path, implode("\n", $lines));
    }

    public function appendToFile(string $file, string $content): void
    {
        $path = $this->directory.'/'.$file;

        file_put_contents($path, file_get_contents($path).$content);
    }

    public function uncomment(string $file, string $search): void
    {
        $path = $this->directory.'/'.$file;

        $lines = explode("\n", file_get_contents($path));

        $lines = array_map(function ($line) use ($search): string {
            if (str_contains($line, $search) && preg_match('/^(\s*)\/\/\s?(.*)$/', $line, $matches)) {
                return $matches[1].$matches[2];
            }

            return $line;
        }, $lines);

        file_put_contents($path, implode("\n", $lines));
    }

    public function stripBlock(string $file, string $tag): void
    {
        $path = $this->directory.'/'.$file;

        $lines = explode("\n", file_get_contents($path));

        [$startPattern, $endPattern] = $this->blockPatterns($tag);

        $result = [];

        foreach ($lines as $line) {
            if (preg_match($startPattern, $line)) {
                continue;
            }
            if (preg_match($endPattern, $line)) {
                continue;
            }
            $result[] = $line;
        }

        file_put_contents($path, implode("\n", $result));
    }

    public function removeBlock(string $file, string $tag): void
    {
        $path = $this->directory.'/'.$file;

        $lines = explode("\n", file_get_contents($path));

        [$startPattern, $endPattern] = $this->blockPatterns($tag);

        $result = [];
        $inBlock = false;

        foreach ($lines as $line) {
            if (preg_match($startPattern, $line)) {
                $inBlock = true;

                continue;
            }

            if ($inBlock && preg_match($endPattern, $line)) {
                $inBlock = false;

                continue;
            }

            if (! $inBlock) {
                $result[] = $line;
            }
        }

        file_put_contents($path, implode("\n", $result));
    }

    /**
     * @return array{string, string}
     */
    protected function blockPatterns(string $tag): array
    {
        $escapedTag = preg_quote($tag, '/');

        return [
            '/^\s*\{?\/\*\s*@'.$escapedTag.'\s*\*\/\}?\s*$/',
            '/^\s*\{?\/\*\s*@end-'.$escapedTag.'\s*\*\/\}?\s*$/',
        ];
    }

    public function publish(string $from): void
    {
        $sourcePath = $this->directory.'/'.$from;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            $relativePath = substr((string) $file->getPathname(), strlen($sourcePath) + 1);

            $this->copy($from.'/'.$relativePath, $relativePath);
        }
    }
}
