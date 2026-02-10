<?php

namespace Laravel\InstallerTools\Tools\Php\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

class RemoveMethodVisitor extends NodeVisitorAbstract
{
    use InteractsWithNodes;

    public function __construct(protected string $name) {}

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->removeMethodFromClass($node);
        }
    }

    protected function removeMethodFromClass(Class_ $class): void
    {
        $class->stmts = array_values(array_filter($class->stmts, fn (\PhpParser\Node\Stmt $stmt): bool => ! ($stmt instanceof ClassMethod && $stmt->name->toString() === $this->name)));
    }
}
