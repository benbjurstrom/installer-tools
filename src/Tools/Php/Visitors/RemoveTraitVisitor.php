<?php

namespace Laravel\InstallerTools\Tools\Php\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeVisitorAbstract;

class RemoveTraitVisitor extends NodeVisitorAbstract
{
    use InteractsWithNodes;

    /** @var array<string> */
    protected array $traits;

    /** @param  string|array<string>  $traits */
    public function __construct(string|array $traits)
    {
        $this->traits = is_array($traits) ? $traits : [$traits];
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->removeTraitsFromClass($node);
        }
    }

    protected function removeTraitsFromClass(Class_ $class): void
    {
        foreach ($class->stmts as $index => $stmt) {
            if (! $stmt instanceof TraitUse) {
                continue;
            }

            $remaining = array_filter($stmt->traits, function (\PhpParser\Node\Name $trait): bool {
                $parts = explode('\\', $trait->toString());
                $simpleName = end($parts);

                return ! in_array($simpleName, $this->traits);
            });

            if ($remaining === []) {
                unset($class->stmts[$index]);
                $class->stmts = array_values($class->stmts);
            } else {
                $newTraitUse = new TraitUse(array_values($remaining));
                $newTraitUse->setAttributes($stmt->getAttributes());
                $class->stmts[$index] = $newTraitUse;
            }
        }
    }
}
