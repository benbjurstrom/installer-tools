<?php

namespace Laravel\Chisel\Tools\Php\Visitors;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeVisitorAbstract;

class AddTraitVisitor extends NodeVisitorAbstract
{
    use InteractsWithNodes;

    /** @var array<string> */
    protected array $traits;

    /** @param  string|array<string>  $traits */
    public function __construct(string|array $traits)
    {
        $this->traits = is_array($traits) ? $traits : [$traits];
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $this->requireClass($nodes);

        return $nodes;
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->addTraitsToClass($node);
        }
    }

    protected function addTraitsToClass(Class_ $class): void
    {
        $existingTraitUse = null;
        $existingIndex = null;

        foreach ($class->stmts as $index => $stmt) {
            if ($stmt instanceof TraitUse) {
                $existingTraitUse = $stmt;
                $existingIndex = $index;

                break;
            }
        }

        $existingNames = [];

        if ($existingTraitUse instanceof \PhpParser\Node\Stmt\TraitUse) {
            foreach ($existingTraitUse->traits as $trait) {
                $existingNames[] = $this->simpleName($trait->toString());
            }
        }

        $traitsToAdd = [];

        foreach ($this->traits as $trait) {
            if (! in_array($this->simpleName($trait), $existingNames)) {
                $traitsToAdd[] = $trait;
            }
        }

        if ($traitsToAdd === []) {
            return;
        }

        if ($existingTraitUse instanceof \PhpParser\Node\Stmt\TraitUse) {
            foreach ($traitsToAdd as $trait) {
                $existingTraitUse->traits[] = new Name($trait);
            }

            $newTraitUse = new TraitUse($existingTraitUse->traits);
            $newTraitUse->setAttributes($existingTraitUse->getAttributes());
            $class->stmts[$existingIndex] = $newTraitUse;
        } else {
            $traitNodes = array_map(fn ($trait): \PhpParser\Node\Name => new Name($trait), $traitsToAdd);

            array_unshift($class->stmts, new TraitUse($traitNodes));
        }
    }
}
