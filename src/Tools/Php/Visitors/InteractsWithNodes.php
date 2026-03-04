<?php

namespace Laravel\InstallerTools\Tools\Php\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use RuntimeException;

trait InteractsWithNodes
{
    protected function hasTypeKeyword(array $ast): bool
    {
        foreach ($this->getStatements($ast) as $node) {
            if ($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Interface_ || $node instanceof Node\Stmt\Trait_) {
                return true;
            }
        }

        return false;
    }

    protected function hasImports(array $ast): bool
    {
        foreach ($this->getStatements($ast) as $node) {
            if ($node instanceof Node\Stmt\Use_) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<Node>  $ast
     * @return array<Node>
     */
    protected function getStatements(array $ast): array
    {
        if (count($ast) === 1 && $ast[0] instanceof Node\Stmt\Namespace_) {
            return $ast[0]->stmts;
        }

        return $ast;
    }

    /**
     * @param  array<Node>  $ast
     * @param  array<Node>  $statements
     * @return array<Node>
     */
    protected function withStatements(array $ast, array $statements): array
    {
        if (count($ast) === 1 && $ast[0] instanceof Node\Stmt\Namespace_) {
            $ast[0]->stmts = $statements;

            return $ast;
        }

        return $statements;
    }

    protected function simpleName(string $name): string
    {
        $parts = explode('\\', $name);

        return end($parts);
    }

    /**
     * @param  array<Node>  $ast
     */
    protected function requireClass(array $ast): void
    {
        foreach ($this->getStatements($ast) as $node) {
            if ($node instanceof Class_) {
                return;
            }
        }

        throw new RuntimeException('Class declaration not found.');
    }
}
