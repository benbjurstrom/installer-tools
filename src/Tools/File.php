<?php

namespace Laravel\Chisel\Tools;

class File
{
    public function __construct(protected string $directory) {}

    public function delete(string ...$paths): void
    {
        foreach ($paths as $path) {
            $fullPath = $this->directory.'/'.$path;

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    public function replace(string $file, string $search, string $replace): void
    {
        $this->write($file, str_replace($search, $replace, $this->read($file)));
    }

    public function removeLinesContaining(string $file, string $content): void
    {
        $lines = explode("\n", $this->read($file));
        $lines = array_values(array_filter($lines, fn (string $line): bool => ! str_contains($line, $content)));

        $this->write($file, implode("\n", $lines));
    }

    public function removeSectionMarkers(string $file, string $tag): void
    {
        $this->rewriteSection($file, $tag, keepContents: true);
    }

    public function removeSection(string $file, string $tag): void
    {
        $this->rewriteSection($file, $tag, keepContents: false);
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

    protected function rewriteSection(string $file, string $tag, bool $keepContents): void
    {
        $lines = explode("\n", $this->read($file));
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

            if ($keepContents || ! $inBlock) {
                $result[] = $line;
            }
        }

        $this->write($file, implode("\n", $result));
    }

    protected function read(string $file): string
    {
        return file_get_contents($this->directory.'/'.$file);
    }

    protected function write(string $file, string $contents): void
    {
        file_put_contents($this->directory.'/'.$file, $contents);
    }
}
