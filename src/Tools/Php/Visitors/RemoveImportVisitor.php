<?php

namespace Laravel\InstallerTools\Tools\Php\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

class RemoveImportVisitor extends NodeVisitorAbstract
{
    use InteractsWithNodes;

    /** @var array<string> */
    protected array $imports;

    /** @param  string|array<string>  $imports */
    public function __construct(string|array $imports)
    {
        $this->imports = is_array($imports) ? $imports : [$imports];
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $isNamespaced = count($nodes) === 1 && $nodes[0] instanceof Node\Stmt\Namespace_;
        $statements = $isNamespaced ? $nodes[0]->stmts : $nodes;

        $filtered = [];

        foreach ($statements as $stmt) {
            if ($stmt instanceof Use_) {
                $remaining = array_filter($stmt->uses, function (\PhpParser\Node\UseItem $use): bool {
                    $fqcn = $use->name->toString();
                    $parts = explode('\\', $fqcn);
                    $simpleName = end($parts);

                    foreach ($this->imports as $import) {
                        if ($fqcn === $import || $simpleName === $import) {
                            return false;
                        }
                    }

                    return true;
                });

                if ($remaining !== []) {
                    $stmt->uses = array_values($remaining);
                    $filtered[] = $stmt;
                }

                continue;
            }

            $filtered[] = $stmt;
        }

        if ($isNamespaced) {
            $nodes[0]->stmts = $filtered;
        } else {
            return $filtered;
        }

        return $nodes;
    }
}
