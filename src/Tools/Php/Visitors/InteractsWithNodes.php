<?php

namespace Laravel\InstallerTools\Tools\Php\Visitors;

use PhpParser\Node;

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
     */
    protected function setStatements(array $ast, array $statements): void
    {
        if (count($ast) === 1 && $ast[0] instanceof Node\Stmt\Namespace_) {
            $ast[0]->stmts = $statements;

            return;
        }
    }
}
