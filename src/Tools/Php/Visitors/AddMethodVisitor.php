<?php

namespace Laravel\InstallerTools\Tools\Php\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use RuntimeException;

class AddMethodVisitor extends NodeVisitorAbstract
{
    use InteractsWithNodes;

    protected ClassMethod $method;

    public function __construct(string $code)
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $stmts = $parser->parse("<?php\nclass __DUMMY__ {\n".$code."\n}");

        if (! isset($stmts[0]) || ! $stmts[0] instanceof Class_ || $stmts[0]->stmts === []) {
            throw new RuntimeException('Could not parse method code.');
        }

        $method = $stmts[0]->stmts[0];

        if (! $method instanceof ClassMethod) {
            throw new RuntimeException('Code does not contain a method declaration.');
        }

        $this->method = $method;
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->addMethodToClass($node);
        }
    }

    protected function addMethodToClass(Class_ $class): void
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && $stmt->name->toString() === $this->method->name->toString()) {
                return;
            }
        }

        $class->stmts[] = $this->method;
    }
}
