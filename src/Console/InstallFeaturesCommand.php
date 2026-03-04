<?php

namespace Laravel\InstallerTools\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class InstallFeaturesCommand extends Command
{
    protected $signature = 'install:features {--answers= : JSON string of answers to skip interactive prompts}';

    protected $description = 'Run the starter kit post-install script to add or remove features';

    public function handle(): int
    {
        $directory = $this->laravel->basePath();
        $kitDirectory = $directory.'/.laravel-installer';
        $scriptPath = $kitDirectory.'/post-install.php';

        if (! file_exists($scriptPath)) {
            $this->components->error('No post-install script found at .laravel-installer/post-install.php');

            return self::FAILURE;
        }

        // ── Confirm destructive action (interactive only) ────────────────

        if (! $this->option('answers')) {
            warning('This will modify files in: '.$directory);
            note('Make sure you can restore changes (e.g. git checkout).');

            if (! confirm('Continue?', default: false)) {
                return self::SUCCESS;
            }
        }

        // ── Run post-install script ──────────────────────────────────────

        $php = (new PhpExecutableFinder)->find(false);

        if ($php === false) {
            $this->components->error('Could not find PHP binary.');

            return self::FAILURE;
        }

        $args = [$php, $scriptPath];
        $answersFile = null;

        if ($this->option('answers')) {
            $answersFile = tempnam(sys_get_temp_dir(), 'kit-answers-');
            file_put_contents($answersFile, $this->option('answers'));
            $args[] = $answersFile;
        }

        try {
            $process = new Process(
                $args,
                $directory,
                ['LARAVEL_INSTALLER_AUTOLOADER' => $this->laravel->basePath('vendor/autoload.php')],
            );

            $process->setTimeout(null);

            if (! $this->option('answers') && Process::isTtySupported()) {
                $process->setTty(true);
            }

            $process->run(function ($type, string $line): void {
                $this->output->write('    '.$line);
            });

            if ($process->isSuccessful()) {
                info('Post-install script completed successfully.');

                (new Filesystem)->deleteDirectory($kitDirectory);

                $this->rebuildAssets($directory);

                return self::SUCCESS;
            }

            $this->components->error('Post-install script failed with exit code: '.$process->getExitCode());

            return $process->getExitCode();
        } finally {
            if ($answersFile !== null && file_exists($answersFile)) {
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
}
