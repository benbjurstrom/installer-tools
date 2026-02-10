<?php

namespace Laravel\InstallerTools\Tools\Php\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

class RemoveInterfaceVisitor extends NodeVisitorAbstract
{
    use InteractsWithNodes;

    /** @var array<string> */
    protected array $interfaces;

    /** @param  string|array<string>  $interfaces */
    public function __construct(string|array $interfaces)
    {
        $this->interfaces = is_array($interfaces) ? $interfaces : [$interfaces];
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->removeInterfacesFromClass($node);
        }
    }

    protected function removeInterfacesFromClass(Class_ $class): void
    {
        $class->implements = array_values(array_filter($class->implements, function (\PhpParser\Node\Name $interface): bool {
            $parts = explode('\\', $interface->toString());
            $simpleName = end($parts);

            return ! in_array($simpleName, $this->interfaces);
        }));
    }
}
