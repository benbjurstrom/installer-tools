<?php

namespace Laravel\InstallerTools\Console;

use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class InstallFeaturesCommand extends Command
{
    protected $signature = 'install:features';

    protected $description = 'Run the starter kit post-install script to add or remove features';

    public function handle(): int
    {
        $directory = $this->laravel->basePath();
        $kitDirectory = $directory.'/.laravel-installer';
        $manifestPath = $kitDirectory.'/manifest.json';

        if (! file_exists($manifestPath)) {
            $this->components->error('No manifest found at .laravel-installer/manifest.json');

            return self::FAILURE;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $prompts = $manifest['prompts'] ?? [];
        $postInstallScript = $manifest['post-install'] ?? 'post-install.php';

        $scriptPath = realpath($kitDirectory.'/'.$postInstallScript);

        if ($scriptPath === false || ! is_file($scriptPath)) {
            $this->components->error("Post-install script not found: {$postInstallScript}");

            return self::FAILURE;
        }

        // ── Warn about destructive changes ──────────────────────────────

        warning('This will modify files in: '.$directory);
        note('Make sure you can restore changes (e.g. git checkout).');

        if (! confirm('Continue?', default: false)) {
            return self::SUCCESS;
        }

        // ── Prompt for answers ──────────────────────────────────────────

        $answers = [];

        foreach ($prompts as $prompt) {
            $answers[$prompt['name']] = $this->promptForAnswer($prompt);
        }

        info('Answers: '.json_encode($answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // ── Run post-install script ─────────────────────────────────────

        $answersFile = tempnam(sys_get_temp_dir(), 'kit-answers-');
        file_put_contents($answersFile, json_encode($answers, JSON_THROW_ON_ERROR));

        $php = (new PhpExecutableFinder)->find(false);

        if ($php === false) {
            $this->components->error('Could not find PHP binary.');

            return self::FAILURE;
        }

        try {
            $process = new Process(
                [$php, $scriptPath, $answersFile],
                $directory,
                ['LARAVEL_INSTALLER_AUTOLOADER' => $this->laravel->basePath('vendor/autoload.php')],
            );

            $process->setTimeout(null);

            if (Process::isTtySupported()) {
                $process->setTty(true);
            }

            $process->run(function ($type, string $line): void {
                $this->output->write('    '.$line);
            });

            if ($process->isSuccessful()) {
                info('Post-install script completed successfully.');

                $this->rebuildAssets($directory);

                return self::SUCCESS;
            }

            $this->components->error('Post-install script failed with exit code: '.$process->getExitCode());

            return $process->getExitCode();
        } finally {
            if (file_exists($answersFile)) {
                unlink($answersFile);
            }
        }
    }

    protected function rebuildAssets(string $directory): void
    {
        info('Installing npm dependencies...');

        $install = new Process(['npm', 'install'], $directory);
        $install->setTimeout(null);

        $install->run(function ($type, string $line): void {
            $this->output->write('    '.$line);
        });

        if (! $install->isSuccessful()) {
            warning('npm install failed. You may need to run "npm install" and "npm run build" manually.');

            return;
        }

        info('Building assets...');

        $build = new Process(['npm', 'run', 'build'], $directory);
        $build->setTimeout(null);

        $build->run(function ($type, string $line): void {
            $this->output->write('    '.$line);
        });

        if ($build->isSuccessful()) {
            info('Assets built successfully.');
        } else {
            warning('Asset build failed. You may need to run "npm run build" manually.');
        }
    }

    protected function promptForAnswer(array $prompt): mixed
    {
        $params = fn (array $keys): array => array_intersect_key($prompt, array_flip($keys));

        return match ($prompt['type']) {
            'text' => text(...$params(['label', 'placeholder', 'default', 'required', 'hint'])),
            'password' => password(...$params(['label', 'placeholder', 'required', 'hint'])),
            'confirm' => confirm(...$params(['label', 'default', 'hint'])),
            'select' => select(...$params(['label', 'options', 'default', 'hint'])),
            'multiselect' => multiselect(...$params(['label', 'options', 'default', 'hint', 'required'])),
            'suggest' => suggest(...$params(['label', 'options', 'placeholder', 'default', 'hint'])),
            default => throw new RuntimeException("Unknown prompt type: {$prompt['type']}"),
        };
    }
}
