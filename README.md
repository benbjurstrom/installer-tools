# Laravel Chisel

Small toolkit for subtractive post-install scripts in Laravel starter kits.

The intended flow is:

1. Ship the starter kit with every optional feature present.
2. Ask the user which features they want.
3. Remove anything they did not select.

`Laravel\Chisel\Chisel` is intentionally narrow. The script API only supports the subtractive operations used by [`example-chisel.php`](example-chisel.php), and the package still ships the `php artisan chisel` runner for executing that script inside a Laravel app.

## Install

```bash
composer require --dev laravel/chisel:dev-main
```

## Run The Script

The package still registers a `chisel` artisan command. That is the expected way to run a starter kit script locally.

```bash
php artisan chisel
```

To run the example file in this repository instead of `chisel.php`, point the command at it:

```bash
php artisan chisel --path=example-chisel.php
```

To skip prompts, pass answers as JSON:

```bash
php artisan chisel --path=example-chisel.php --answers='{"auth_features":["email-verification"]}'
```

Pass `--delete-script` to remove the script after a successful run.

## Example

```php
<?php

require getenv('LARAVEL_INSTALLER_AUTOLOADER');

use Laravel\Chisel\Chisel;

$c = Chisel::in(dirname(__DIR__))
    ->withAnswers($argv[1] ?? null)
    ->multiselect('auth_features', 'Which authentication features would you like to enable?', [
        'email-verification' => 'Email verification',
        '2fa' => 'Two-factor authentication',
        'passkeys' => 'Passkeys',
    ], hint: 'Use space to select, enter to confirm.');

$c->selected('auth_features', 'email-verification',
    then: function (Chisel $c) {
        $c->files(
            'resources/js/pages/settings/profile.tsx',
            'app/Providers/FortifyServiceProvider.php',
        )->removeSectionMarkers('email-verification');
    },
    else: function (Chisel $c) {
        $c->phpFile('app/Models/User.php')
            ->removeImport('Illuminate\Contracts\Auth\MustVerifyEmail')
            ->removeInterface('MustVerifyEmail');

        $c->file('config/fortify.php')->removeLinesContaining('Features::emailVerification()');

        $c->files(
            'app/Providers/FortifyServiceProvider.php',
            'resources/js/pages/settings/profile.tsx',
        )->removeSection('email-verification');

        $c->files(
            'resources/js/components/email-verification-notice.tsx',
            'resources/js/pages/auth/verify-email.tsx',
            'tests/Feature/Auth/EmailVerificationTest.php',
            'tests/Feature/Auth/VerificationNotificationTest.php',
        )->delete();
    },
);
```

`withAnswers()` accepts a JSON string. Pass `null` to prompt interactively, or pass a JSON payload in `$argv[1]` to skip prompts in non-interactive runs.

## Supported API

### Prompts and branching

| Method | Purpose |
|---|---|
| `withAnswers(?string $json)` | Hydrate answers from JSON |
| `multiselect($name, $label, $options, $default = [], $hint = '', $required = false)` | Ask for feature selections |
| `selected($key, $value, then:, else:)` | Branch on a multiselect answer |

### File mutations

`file($path)` targets one file. `files(...$paths)` targets many files.

| Method | Purpose |
|---|---|
| `replace($search, $replace)` | String replacement |
| `removeLinesContaining($content)` | Remove matching lines |
| `removeSectionMarkers($tag)` | Remove markers and keep the code inside |
| `removeSection($tag)` | Remove the marked section entirely |
| `delete()` | Delete the targeted files |

### PHP AST removals

`phpFile($path)` batches AST edits and saves automatically when the object is destroyed.

| Method | Purpose |
|---|---|
| `removeImport($class)` | Remove a `use` statement |
| `removeTrait($trait)` | Remove a trait use from the class |
| `removeInterface($interface)` | Remove an implemented interface |

### Package manager

| Method | Purpose |
|---|---|
| `npm()->remove(...$packages)` | Remove npm packages |

## Section markers

Use comment pairs to wrap optional code:

```php
/* @passkeys */
Fortify::authenticateUsingPasskeys();
/* @end-passkeys */
```

JS and JSX files can use block comments with braces:

```tsx
{/* @passkeys */}
<PasskeyButton />
{/* @end-passkeys */}
```

`removeSectionMarkers('passkeys')` keeps the code and strips the markers.
`removeSection('passkeys')` removes both the markers and the enclosed code.
