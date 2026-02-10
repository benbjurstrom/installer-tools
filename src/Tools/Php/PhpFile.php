<?php

namespace Laravel\InstallerTools\Tools\Php;

use Laravel\InstallerTools\Tools\Php\Visitors\AddImportVisitor;
use Laravel\InstallerTools\Tools\Php\Visitors\AddInterfaceVisitor;
use Laravel\InstallerTools\Tools\Php\Visitors\AddMethodVisitor;
use Laravel\InstallerTools\Tools\Php\Visitors\AddToArrayVisitor;
use Laravel\InstallerTools\Tools\Php\Visitors\AddTraitVisitor;
use Laravel\InstallerTools\Tools\Php\Visitors\RemoveFromArrayVisitor;
use Laravel\InstallerTools\Tools\Php\Visitors\RemoveImportVisitor;
use Laravel\InstallerTools\Tools\Php\Visitors\RemoveInterfaceVisitor;
use Laravel\InstallerTools\Tools\Php\Visitors\RemoveMethodVisitor;
use Laravel\InstallerTools\Tools\Php\Visitors\RemoveTraitVisitor;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class PhpFile
{
    /** @var array<NodeVisitorAbstract> */
    protected array $edits = [];

    public function __construct(protected string $path) {}

    public function addTrait(string $trait): static
    {
        $this->edits[] = new AddTraitVisitor($trait);

        return $this;
    }

    public function removeTrait(string $trait): static
    {
        $this->edits[] = new RemoveTraitVisitor($trait);

        return $this;
    }

    public function addInterface(string $interface): static
    {
        $this->edits[] = new AddInterfaceVisitor($interface);

        return $this;
    }

    public function removeInterface(string $interface): static
    {
        $this->edits[] = new RemoveInterfaceVisitor($interface);

        return $this;
    }

    public function addImport(string $class): static
    {
        $this->edits[] = new AddImportVisitor($class);

        return $this;
    }

    public function removeImport(string $class): static
    {
        $this->edits[] = new RemoveImportVisitor($class);

        return $this;
    }

    public function addToArray(string $target, mixed ...$args): static
    {
        if (count($args) === 1) {
            $this->edits[] = new AddToArrayVisitor($target, null, $args[0]);
        } elseif (count($args) === 2) {
            $this->edits[] = new AddToArrayVisitor($target, $args[0], $args[1]);
        } else {
            throw new \InvalidArgumentException('addToArray expects 2 or 3 arguments.');
        }

        return $this;
    }

    public function removeFromArray(string $target, string $search): static
    {
        $this->edits[] = new RemoveFromArrayVisitor($target, $search);

        return $this;
    }

    public function addMethod(string $code): static
    {
        $this->edits[] = new AddMethodVisitor($code);

        return $this;
    }

    public function removeMethod(string $name): static
    {
        $this->edits[] = new RemoveMethodVisitor($name);

        return $this;
    }

    public function save(): void
    {
        if ($this->edits === []) {
            return;
        }

        $code = file_get_contents($this->path);

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $oldStmts = $parser->parse($code);
        $oldTokens = $parser->getTokens();

        $cloner = new NodeTraverser;
        $cloner->addVisitor(new CloningVisitor);
        $newStmts = $cloner->traverse($oldStmts);

        $traverser = new NodeTraverser;

        foreach ($this->edits as $edit) {
            $traverser->addVisitor($edit);
        }

        $traverser->traverse($newStmts);

        file_put_contents(
            $this->path,
            (new Standard)->printFormatPreserving($newStmts, $oldStmts, $oldTokens),
        );

        $this->edits = [];
    }

    public function __destruct()
    {
        $this->save();
    }
}
