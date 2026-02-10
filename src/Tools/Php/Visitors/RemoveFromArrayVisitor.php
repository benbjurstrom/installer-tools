<?php

namespace Laravel\InstallerTools\Tools\Php\Visitors;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;

class RemoveFromArrayVisitor extends NodeVisitorAbstract
{
    use InteractsWithNodes;

    public function __construct(
        protected string $target,
        protected string $search,
    ) {}

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->removeFromArray($node);
        }
    }

    protected function removeFromArray(Class_ $class): void
    {
        $array = $this->findTargetArray($class);

        if (! $array instanceof \PhpParser\Node\Expr\Array_) {
            return;
        }

        $array->items = array_values(array_filter($array->items, function (?ArrayItem $item): bool {
            if (! $item instanceof \PhpParser\Node\Expr\ArrayItem) {
                return true;
            }

            if ($item->key instanceof String_ && $item->key->value === $this->search) {
                return false;
            }

            return ! (! $item->key instanceof \PhpParser\Node\Expr && $item->value instanceof String_ && $item->value->value === $this->search);
        }));
    }

    protected function findTargetArray(Class_ $class): ?Array_
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->name->toString() === $this->target && $prop->default instanceof Array_) {
                        return $prop->default;
                    }
                }
            }

            if ($stmt instanceof ClassMethod && $stmt->name->toString() === $this->target) {
                return $this->findReturnArray($stmt);
            }
        }

        return null;
    }

    protected function findReturnArray(ClassMethod $method): ?Array_
    {
        foreach ($method->stmts ?? [] as $stmt) {
            if ($stmt instanceof Return_ && $stmt->expr instanceof Array_) {
                return $stmt->expr;
            }
        }

        return null;
    }
}
