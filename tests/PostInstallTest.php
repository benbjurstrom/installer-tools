<?php

use Laravel\InstallerTools\PostInstall;
use Laravel\InstallerTools\Tools\Php\PhpFile;

beforeEach(function (): void {
    $this->tempDir = __DIR__.'/../tests-output/project-'.uniqid();

    mkdir($this->tempDir, 0777, true);
});

afterEach(function (): void {
    if (file_exists($this->tempDir)) {
        if (PHP_OS_FAMILY === 'Windows') {
            system("rd /s /q \"{$this->tempDir}\"");
        } else {
            system("rm -rf \"{$this->tempDir}\"");
        }
    }
});

it('calls callback when selected value is in array', function (): void {
    $answersPath = $this->tempDir.'/answers.json';
    file_put_contents($answersPath, json_encode(['features' => ['2fa', 'passkeys']]));

    $called = false;

    PostInstall::in($this->tempDir)
        ->withAnswers($answersPath)
        ->selected('features', '2fa', function () use (&$called): void {
            $called = true;
        });

    expect($called)->toBeTrue();
});

it('does not call callback when selected value is not in array', function (): void {
    $answersPath = $this->tempDir.'/answers.json';
    file_put_contents($answersPath, json_encode(['features' => ['2fa']]));

    $called = false;

    PostInstall::in($this->tempDir)
        ->withAnswers($answersPath)
        ->selected('features', 'passkeys', function () use (&$called): void {
            $called = true;
        });

    expect($called)->toBeFalse();
});

it('calls callback when confirmed is true', function (): void {
    $answersPath = $this->tempDir.'/answers.json';
    file_put_contents($answersPath, json_encode(['seed' => true]));

    $called = false;

    PostInstall::in($this->tempDir)
        ->withAnswers($answersPath)
        ->confirmed('seed', function () use (&$called): void {
            $called = true;
        });

    expect($called)->toBeTrue();
});

it('does not call callback when confirmed is false', function (): void {
    $answersPath = $this->tempDir.'/answers.json';
    file_put_contents($answersPath, json_encode(['seed' => false]));

    $called = false;

    PostInstall::in($this->tempDir)
        ->withAnswers($answersPath)
        ->confirmed('seed', function () use (&$called): void {
            $called = true;
        });

    expect($called)->toBeFalse();
});

it('calls callback when answered value matches', function (): void {
    $answersPath = $this->tempDir.'/answers.json';
    file_put_contents($answersPath, json_encode(['stack' => 'react']));

    $called = false;

    PostInstall::in($this->tempDir)
        ->withAnswers($answersPath)
        ->answered('stack', 'react', function () use (&$called): void {
            $called = true;
        });

    expect($called)->toBeTrue();
});

it('does not call callback when answered value differs', function (): void {
    $answersPath = $this->tempDir.'/answers.json';
    file_put_contents($answersPath, json_encode(['stack' => 'vue']));

    $called = false;

    PostInstall::in($this->tempDir)
        ->withAnswers($answersPath)
        ->answered('stack', 'react', function () use (&$called): void {
            $called = true;
        });

    expect($called)->toBeFalse();
});

it('copies a file', function (): void {
    mkdir($this->tempDir.'/src');
    file_put_contents($this->tempDir.'/src/original.txt', 'hello');

    PostInstall::in($this->tempDir)->copy('src/original.txt', 'dest/copied.txt');

    expect($this->tempDir.'/dest/copied.txt')
        ->toBeFile()
        ->and(file_get_contents($this->tempDir.'/dest/copied.txt'))->toBe('hello');
});

it('replaces content in a file', function (): void {
    file_put_contents($this->tempDir.'/config.txt', 'APP_NAME=Laravel');

    PostInstall::in($this->tempDir)->replaceInFile('config.txt', 'Laravel', 'MyApp');

    expect(file_get_contents($this->tempDir.'/config.txt'))->toBe('APP_NAME=MyApp');
});

it('sets an env value', function (): void {
    file_put_contents($this->tempDir.'/.env', "APP_NAME=Laravel\nAPP_URL=http://localhost\n");

    PostInstall::in($this->tempDir)->env('APP_NAME', 'MyApp');

    $contents = file_get_contents($this->tempDir.'/.env');

    expect($contents)
        ->toContain('APP_NAME=MyApp')
        ->toContain('APP_URL=http://localhost');
});

it('adds a trait to a php file', function (): void {
    $fixture = $this->tempDir.'/User.php';
    copy(__DIR__.'/fixtures/php/SampleClass.php.stub', $fixture);

    (new PhpFile($fixture))->addTrait('SoftDeletes')->save();

    expect(file_get_contents($fixture))->toContain('use SoftDeletes;');
});

it('adds an import to a php file', function (): void {
    $fixture = $this->tempDir.'/User.php';
    copy(__DIR__.'/fixtures/php/SampleClass.php.stub', $fixture);

    (new PhpFile($fixture))->addImport('Illuminate\Database\Eloquent\SoftDeletes')->save();

    expect(file_get_contents($fixture))->toContain('use Illuminate\Database\Eloquent\SoftDeletes;');
});

it('removes a trait from a php file', function (): void {
    $fixture = $this->tempDir.'/Item.php';
    copy(__DIR__.'/fixtures/php/ClassWithTrait.php.stub', $fixture);

    (new PhpFile($fixture))->removeTrait('SoftDeletes')->save();

    expect(file_get_contents($fixture))->not->toContain('use SoftDeletes;');
});

it('adds an interface to a php file', function (): void {
    $fixture = $this->tempDir.'/User.php';
    copy(__DIR__.'/fixtures/php/SampleClass.php.stub', $fixture);

    (new PhpFile($fixture))->addInterface('MustVerifyEmail')->save();

    expect(file_get_contents($fixture))->toContain('implements MustVerifyEmail');
});

it('does not add a duplicate interface', function (): void {
    $fixture = $this->tempDir.'/User.php';
    copy(__DIR__.'/fixtures/php/SampleClass.php.stub', $fixture);

    (new PhpFile($fixture))->addInterface('MustVerifyEmail')->save();
    (new PhpFile($fixture))->addInterface('MustVerifyEmail')->save();

    expect(substr_count(file_get_contents($fixture), 'MustVerifyEmail'))->toBe(1);
});

it('does not add a duplicate trait', function (): void {
    $fixture = $this->tempDir.'/Item.php';
    copy(__DIR__.'/fixtures/php/ClassWithTrait.php.stub', $fixture);

    (new PhpFile($fixture))->addTrait('SoftDeletes')->save();

    expect(preg_match_all('/^\s+use\s+SoftDeletes;$/m', file_get_contents($fixture)))->toBe(1);
});
