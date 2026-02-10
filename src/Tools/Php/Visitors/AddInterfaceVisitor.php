<?php

namespace Laravel\InstallerTools\Tools\Php\Visitors;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;
use RuntimeException;

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
        $hasClass = false;

        foreach ($this->getStatements($nodes) as $node) {
            if ($node instanceof Class_) {
                $hasClass = true;

                break;
            }
        }

        if (! $hasClass) {
            throw new RuntimeException('Class declaration not found.');
        }

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
        $existingNames = [];

        foreach ($class->implements as $interface) {
            $parts = explode('\\', $interface->toString());
            $existingNames[] = end($parts);
        }

        foreach ($this->interfaces as $interface) {
            $parts = explode('\\', $interface);
            $simpleName = end($parts);

            if (! in_array($simpleName, $existingNames)) {
                $class->implements[] = new Name($interface);
            }
        }
    }
}
