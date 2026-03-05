<?php

namespace Laravel\Chisel\Tools\Php\Visitors;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

class AddInterfaceVisitor extends NodeVisitorAbstract
{
    use InteractsWithNodes;

    /** @var array<string> */
    protected array $interfaces;

    /** @param  string|array<string>  $interfaces */
    public function __construct(string|array $interfaces)
    {
        $this->interfaces = is_array($interfaces) ? $interfaces : [$interfaces];
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $this->requireClass($nodes);

        return $nodes;
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->addInterfacesToClass($node);
        }
    }

    protected function addInterfacesToClass(Class_ $class): void
    {
        $existingNames = array_map(
            fn (\PhpParser\Node\Name $interface): string => $this->simpleName($interface->toString()),
            $class->implements,
        );

        foreach ($this->interfaces as $interface) {
            if (! in_array($this->simpleName($interface), $existingNames)) {
                $class->implements[] = new Name($interface);
            }
        }
    }
}
