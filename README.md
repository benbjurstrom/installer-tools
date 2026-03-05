# Laravel Chisel

Toolkit for building post-install customization scripts in Laravel starter kits. Starter kits ship with all features enabled; the chisel script subtracts anything the user didn't select.

## How it works

Each starter kit includes a `chisel.php` script. The script prompts the user for their preferences using [Laravel Prompts](https://laravel.com/docs/prompts), then modifies the project based on their answers.

During `laravel new`, the installer runs the chisel script as a subprocess. The `Chisel` class provides the API used inside that script.

## Testing with `php artisan chisel`

The package registers a `chisel` artisan command so you can test the chisel flow without running `laravel new`. Add the repository and install the package as a dev dependency in your starter kit:

```bash
composer require --dev laravel/chisel:dev-main
```

Then run:

```bash
php artisan chisel
```

The command runs the chisel script (which prompts interactively), then rebuilds frontend assets afterward.
After a successful run, you'll be prompted to delete the chisel script. To delete it automatically, pass `--delete-script`.

For CI or non-interactive use, pass answers as JSON to skip prompts:

```bash
php artisan chisel --answers='{"auth_features": ["email-verification", "2fa"]}'
```

```bash
php artisan chisel --delete-script
```

## Chisel script example

```php
<?php

require getenv('LARAVEL_INSTALLER_AUTOLOADER');

use Laravel\Chisel\Chisel;

$install = Chisel::in(dirname(__DIR__))
    ->withAnswers($argv[1] ?? null)
    ->multiselect('auth_features', 'Which authentication features would you like to enable?', [
        'email-verification' => 'Email verification',
        '2fa' => 'Two-factor authentication',
    ], hint: 'Use space to select, enter to confirm.');

$install->selected('auth_features', 'email-verification',
    then: function ($install) {
        // Feature selected — remove the section markers, keep the code
        $install->files(
            'app/Providers/FortifyServiceProvider.php',
            'resources/js/pages/settings/profile.tsx',
        )->removeSectionMarkers('email-verification');
    },
    else: function ($install) {
        // Feature NOT selected — remove the code and related files
        $install->phpFile('app/Models/User.php')
            ->removeImport('Illuminate\Contracts\Auth\MustVerifyEmail')
            ->removeInterface('MustVerifyEmail');

        $install->files(
            'app/Providers/FortifyServiceProvider.php',
            'resources/js/pages/settings/profile.tsx',
        )->removeSection('email-verification');

        $install->files(
            'resources/js/components/email-verification-notice.tsx',
            'tests/Feature/Auth/EmailVerificationTest.php',
        )->delete();
    },
);
```

`withAnswers` takes a JSON string. When non-null, prompts are skipped and the decoded answers are used. When `null`, prompts are shown interactively.

## API overview

### Prompts

Prompt methods present a [Laravel Prompts](https://laravel.com/docs/prompts) question and store the answer. If answers were pre-supplied via `withAnswers`, the prompt is skipped.

| Method | Description |
|---|---|
| `confirm($name, $label, ...)` | Yes/no question |
| `select($name, $label, $options, ...)` | Single choice |
| `multiselect($name, $label, $options, ...)` | Multiple choice |

### Branching on answers

| Method | Description |
|---|---|
| `selected($key, $value, then:, else:)` | Runs `then` if `$value` is in the multiselect answer for `$key`, otherwise runs `else` |
| `confirmed($key, then:, else:)` | Runs `then` if the confirm answer for `$key` is true |
| `answered($key, $value, then:, else:)` | Runs `then` if the answer for `$key` exactly equals `$value` |

### Section markers

Section markers are comment pairs that delimit feature-specific code:

```php
/* @email-verification */
Fortify::verifyEmailView(fn () => Inertia::render('auth/verify-email'));
/* @end-email-verification */
```

In JSX files, use `{/* @tag */}` / `{/* @end-tag */}` syntax.

| Method | Description |
|---|---|
| `files(...$paths)->removeSectionMarkers($tag)` | Remove the markers, keep the code between them |
| `files(...$paths)->removeSection($tag)` | Remove the markers and the code between them |

### File operations

`file(...$paths)` / `files(...$paths)` returns a `PendingFiles` instance that targets one or more files.

| Method | Description |
|---|---|
| `file($from)->copyTo($to)` | Copy a file |
| `files(...$paths)->delete()` | Delete files |
| `file($path)->replace($search, $replace)` | String replacement |
| `file($path)->removeLinesContaining($content)` | Remove lines containing a string (single-line targets only) |
| `file($path)->replaceLine($search, $replace)` | Replace the entire line containing a string |
| `file($path)->append($content)` | Append to a file |
| `file($path)->appendAfterLine($search, $content)` | Append content after line containing a string |
| `file($path)->uncomment($search)` | Uncomment lines matching a string |
| `file($from)->publish()` | Publish files from a directory |

### PHP AST modifications

`$kit->phpFile($path)` returns a `PhpFile` instance for AST-based edits (using nikic/php-parser). Edits are batched and saved automatically.

| Method | Description |
|---|---|
| `addImport($class)` / `removeImport($class)` | Add or remove a `use` statement |
| `addTrait($trait)` / `removeTrait($trait)` | Add or remove a trait use |
| `addInterface($iface)` / `removeInterface($iface)` | Add or remove an interface |
| `addMethod($code)` / `removeMethod($name)` | Add or remove a method |

### Package and environment tools

| Method | Description |
|---|---|
| `composer()->require(...$pkgs)` | Require composer packages |
| `composer()->requireDev(...$pkgs)` | Require dev composer packages |
| `composer()->remove(...$pkgs)` | Remove composer packages |
| `npm()->install(...$pkgs)` | Install npm packages |
| `npm()->installDev(...$pkgs)` | Install dev npm packages |
| `npm()->remove(...$pkgs)` | Remove npm packages |
| `artisan($command)` | Run an artisan command |
| `env($key, $value)` | Set a `.env` value |
| `config($file, $key, $value)` | Set a config value |
| `run($command)` | Run an arbitrary shell command |
