<?php

namespace Laravel\InstallerTools\Tools\Php\Visitors;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;
use RuntimeException;

class AddToArrayVisitor extends NodeVisitorAbstract
{
    use InteractsWithNodes;

    public function __construct(
        protected string $target,
        protected ?string $key,
        protected mixed $value,
    ) {}

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->addToArray($node);
        }
    }

    protected function addToArray(Class_ $class): void
    {
        $array = $this->findTargetArray($class);

        if (! $array instanceof \PhpParser\Node\Expr\Array_) {
            return;
        }

        if ($this->isDuplicate($array)) {
            return;
        }

        $valueNode = $this->createValueNode($this->value);
        $keyNode = $this->key !== null ? new String_($this->key) : null;

        $array->items[] = new ArrayItem($valueNode, $keyNode);
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

    protected function isDuplicate(Array_ $array): bool
    {
        foreach ($array->items as $item) {
            if ($item === null) {
                continue;
            }

            if ($this->key !== null) {
                if ($item->key instanceof String_ && $item->key->value === $this->key) {
                    return true;
                }
            } elseif ($item->key === null && $item->value instanceof String_ && $item->value->value === $this->value) {
                return true;
            }
        }

        return false;
    }

    protected function createValueNode(mixed $value): Node\Expr
    {
        return match (true) {
            is_string($value) => new String_($value),
            is_int($value) => new Int_($value),
            is_float($value) => new Float_($value),
            is_bool($value) => new ConstFetch(new Name($value ? 'true' : 'false')),
            is_null($value) => new ConstFetch(new Name('null')),
            default => throw new RuntimeException('Unsupported value type: '.gettype($value)),
        };
    }
}
