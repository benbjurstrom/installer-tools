# Laravel Installer Tools

Toolkit for building post-install customization scripts in Laravel starter kits. Starter kits ship with all features enabled; the post-install script subtracts anything the user didn't select.

## How it works

Each starter kit includes a `.laravel-installer/` directory with two files:

- **`manifest.json`** — Declares the prompts shown to the user during `laravel new` (or `php artisan install:features`).
- **`post-install.php`** — Script that receives the user's answers and modifies the project accordingly.

The `laravel new` command reads the manifest, collects answers, and runs the post-install script as a subprocess. The `PostInstall` class provides the API used inside that script.

## Testing with `install:features`

The package registers an `install:features` artisan command so you can test the post-install flow without running `laravel new`. Add the repository and install the package as a dev dependency in your starter kit:

```bash
composer config repositories.installer-tools vcs https://github.com/benbjurstrom/installer-tools
composer require --dev benbjurstrom/installer-tools:dev-main
```

Then run:

```bash
php artisan install:features
```

The command reads the manifest, presents the same prompts, runs the post-install script, and rebuilds frontend assets afterward.

## Manifest format

```json
{
    "prompts": [
        {
            "type": "multiselect",
            "name": "auth_features",
            "label": "Which authentication features would you like to enable?",
            "options": {
                "email-verification": "Email verification",
                "2fa": "Two-factor authentication"
            },
            "default": [],
            "hint": "Use space to select, enter to confirm."
        }
    ]
}
```

Supported prompt types: `text`, `password`, `confirm`, `select`, `multiselect`, `suggest`. These map directly to [Laravel Prompts](https://laravel.com/docs/prompts) functions.

## Post-install script example

```php
<?php

require getenv('LARAVEL_INSTALLER_AUTOLOADER');

use Laravel\InstallerTools\PostInstall;

$kit = PostInstall::in(dirname(__DIR__))
    ->withAnswers($argv[1]);

$kit->selected('auth_features', 'email-verification', function ($kit) {
    // Feature selected — strip the block markers, keep the code
    $kit->stripBlock('app/Providers/FortifyServiceProvider.php', 'email-verification');
}, function ($kit) {
    // Feature NOT selected — remove the code and related files
    $kit->php('app/Models/User.php')
        ->removeImport('Illuminate\Contracts\Auth\MustVerifyEmail')
        ->removeInterface('MustVerifyEmail');

    $kit->removeBlock('app/Providers/FortifyServiceProvider.php', 'email-verification');

    $kit->delete(
        'resources/js/components/email-verification-notice.tsx',
        'tests/Feature/Auth/EmailVerificationTest.php',
    );
});
```

### Block markers

Block markers are comment pairs that delimit feature-specific code:

```php
/* @email-verification */
Fortify::verifyEmailView(fn () => Inertia::render('auth/verify-email'));
/* @end-email-verification */
```

In JSX files, use `{/* @tag */}` / `{/* @end-tag */}` syntax.

| Method | Description |
|---|---|
| `stripBlock($file, $tag)` | Remove the markers, keep the code between them |
| `removeBlock($file, $tag)` | Remove the markers and the code between them |

### File operations

| Method | Description |
|---|---|
| `copy($from, $to)` | Copy a file |
| `delete(...$paths)` | Delete files |
| `replaceInFile($file, $search, $replace)` | String replacement |
| `deleteLinesContaining($file, $content)` | Delete lines containing a string (single-line targets only) |
| `appendToFile($file, $content)` | Append to a file |
| `uncomment($file, $search)` | Uncomment lines matching a string |
| `publish($from)` | Publish a file from the `.laravel-installer/` directory |

### PHP AST modifications

`$kit->php($path)` returns a `PhpFile` instance for AST-based edits (using nikic/php-parser). Edits are batched and saved automatically.

| Method | Description |
|---|---|
| `addImport($class)` / `removeImport($class)` | Add or remove a `use` statement |
| `addTrait($trait)` / `removeTrait($trait)` | Add or remove a trait use |
| `addInterface($iface)` / `removeInterface($iface)` | Add or remove an interface |
| `addToArray($target, ...)` / `removeFromArray($target, $key)` | Add or remove items from a property array |
| `addMethod($code)` / `removeMethod($name)` | Add or remove a method |

### Package and environment tools

| Method | Description |
|---|---|
| `composer($cmd, ...$pkgs)` | Run composer (`require`, `require-dev`, `remove`) |
| `npm($cmd, ...$pkgs)` | Run npm (`install`, `install-dev`, `remove`) |
| `artisan($command)` | Run an artisan command |
| `env($key, $value)` | Set a `.env` value |
| `config($file, $key, $value)` | Set a config value |
| `run($command)` | Run an arbitrary shell command |
