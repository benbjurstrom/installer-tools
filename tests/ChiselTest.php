<?php

use Laravel\Chisel\Chisel;
use Laravel\Chisel\Tools\Php\PhpFile;

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
    $called = false;

    Chisel::in($this->tempDir)
        ->withAnswers(json_encode(['features' => ['2fa', 'passkeys']]))
        ->selected('features', '2fa', then: function () use (&$called): void {
            $called = true;
        });

    expect($called)->toBeTrue();
});

it('does not call callback when selected value is not in array', function (): void {
    $called = false;

    Chisel::in($this->tempDir)
        ->withAnswers(json_encode(['features' => ['2fa']]))
        ->selected('features', 'passkeys', then: function () use (&$called): void {
            $called = true;
        });

    expect($called)->toBeFalse();
});

it('calls callback when confirmed is true', function (): void {
    $called = false;

    Chisel::in($this->tempDir)
        ->withAnswers(json_encode(['seed' => true]))
        ->confirmed('seed', then: function () use (&$called): void {
            $called = true;
        });

    expect($called)->toBeTrue();
});

it('does not call callback when confirmed is false', function (): void {
    $called = false;

    Chisel::in($this->tempDir)
        ->withAnswers(json_encode(['seed' => false]))
        ->confirmed('seed', then: function () use (&$called): void {
            $called = true;
        });

    expect($called)->toBeFalse();
});

it('calls callback when answered value matches', function (): void {
    $called = false;

    Chisel::in($this->tempDir)
        ->withAnswers(json_encode(['stack' => 'react']))
        ->answered('stack', 'react', then: function () use (&$called): void {
            $called = true;
        });

    expect($called)->toBeTrue();
});

it('does not call callback when answered value differs', function (): void {
    $called = false;

    Chisel::in($this->tempDir)
        ->withAnswers(json_encode(['stack' => 'vue']))
        ->answered('stack', 'react', then: function () use (&$called): void {
            $called = true;
        });

    expect($called)->toBeFalse();
});

it('copies a file', function (): void {
    mkdir($this->tempDir.'/src');
    file_put_contents($this->tempDir.'/src/original.txt', 'hello');

    Chisel::in($this->tempDir)->file('src/original.txt')->copyTo('dest/copied.txt');

    expect($this->tempDir.'/dest/copied.txt')
        ->toBeFile()
        ->and(file_get_contents($this->tempDir.'/dest/copied.txt'))->toBe('hello');
});

it('throws when copyTo targets multiple source paths', function (): void {
    mkdir($this->tempDir.'/src');
    file_put_contents($this->tempDir.'/src/a.txt', 'a');
    file_put_contents($this->tempDir.'/src/b.txt', 'b');

    expect(fn (): mixed => Chisel::in($this->tempDir)
        ->files('src/a.txt', 'src/b.txt')
        ->copyTo('dest/copied.txt'))
        ->toThrow(\InvalidArgumentException::class, 'requires exactly one path');
});

it('throws when publish targets multiple source paths', function (): void {
    mkdir($this->tempDir.'/stubs-a', 0777, true);
    mkdir($this->tempDir.'/stubs-b', 0777, true);

    expect(fn (): mixed => Chisel::in($this->tempDir)
        ->files('stubs-a', 'stubs-b')
        ->publish())
        ->toThrow(\InvalidArgumentException::class, 'requires exactly one path');
});

it('supports fluent file operation chaining', function (): void {
    file_put_contents($this->tempDir.'/fluent.txt', "name=Laravel\n// enabled=true\n");

    Chisel::in($this->tempDir)
        ->file('fluent.txt')
        ->replace('Laravel', 'Chisel')
        ->uncomment('enabled=true')
        ->append("\nstatus=ok");

    expect(file_get_contents($this->tempDir.'/fluent.txt'))
        ->toContain('name=Chisel')
        ->toContain('enabled=true')
        ->toContain('status=ok');
});

it('replaces content in a file', function (): void {
    file_put_contents($this->tempDir.'/config.txt', 'APP_NAME=Laravel');

    Chisel::in($this->tempDir)->file('config.txt')->replace('Laravel', 'MyApp');

    expect(file_get_contents($this->tempDir.'/config.txt'))->toBe('APP_NAME=MyApp');
});

it('sets an env value', function (): void {
    file_put_contents($this->tempDir.'/.env', "APP_NAME=Laravel\nAPP_URL=http://localhost\n");

    Chisel::in($this->tempDir)->env('APP_NAME', 'MyApp');

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
