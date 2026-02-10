<?php

namespace Laravel\InstallerTools\Tools\Php\Visitors;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;
use RuntimeException;

class AddImportVisitor extends NodeVisitorAbstract
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
        if (! $this->hasTypeKeyword($nodes)) {
            throw new RuntimeException('Class, interface, or trait declaration not found.');
        }

        $isNamespaced = count($nodes) === 1 && $nodes[0] instanceof Node\Stmt\Namespace_;
        $statements = $isNamespaced ? $nodes[0]->stmts : $nodes;

        $existingImports = [];
        $lastImportIndex = -1;

        foreach ($statements as $index => $stmt) {
            if ($stmt instanceof Use_) {
                foreach ($stmt->uses as $use) {
                    $existingImports[] = $use->name->toString();
                }

                $lastImportIndex = $index;
            }
        }

        $newImports = [];

        foreach ($this->imports as $import) {
            if (! in_array($import, $existingImports)) {
                $newImports[] = new Use_([new UseItem(new Name($import))]);
            }
        }

        if ($newImports !== []) {
            $insertAt = $lastImportIndex >= 0 ? $lastImportIndex + 1 : 0;

            array_splice($statements, $insertAt, 0, $newImports);

            if ($isNamespaced) {
                $nodes[0]->stmts = $statements;
            } else {
                return $statements;
            }
        }

        return $nodes;
    }
}
