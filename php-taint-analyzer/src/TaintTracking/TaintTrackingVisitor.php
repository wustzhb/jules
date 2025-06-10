<?php

namespace PhpTaintAnalyzer\TaintTracking;

use PhpParser\Node;
use PhpParser\Node\Expr; // Added for type hinting Expr
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\NodeVisitorAbstract;

class TaintTrackingVisitor extends NodeVisitorAbstract
{
    private TaintState $taintState;

    public function __construct(TaintState $taintState)
    {
        $this->taintState = $taintState;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Assign) {
            $this->handleAssignment($node);
        }
        return null; // Continue traversal
    }

    private function getVariableName(Expr $expr): ?string
    {
        if ($expr instanceof Variable && is_string($expr->name)) {
            return $expr->name;
        }
        return null;
    }

    /**
     * Recursively collects taint sources from a concatenation expression.
     * @param Expr $expr The expression to check (part of a Concat node).
     * @param array $taintedSourcesCollection Array to collect taint information.
     */
    private function collectTaintFromConcatParts(Expr $expr, array &$taintedSourcesCollection): void
    {
        if ($expr instanceof Concat) {
            $this->collectTaintFromConcatParts($expr->left, $taintedSourcesCollection);
            $this->collectTaintFromConcatParts($expr->right, $taintedSourcesCollection);
        } elseif ($expr instanceof Variable) {
            $varName = $this->getVariableName($expr);
            if ($varName && $this->taintState->isVariableTainted($varName)) {
                foreach ($this->taintState->getTaintSources($varName) as $originalSource) {
                    $taintedSourcesCollection[] = "concat part \${$varName} (source: {$originalSource})";
                }
            }
        }
        // Other expression types within a concat (e.g., literals, function calls) are ignored for now
        // or would require further logic if they could introduce or alter taint.
    }

    private function handleAssignment(Assign $assignNode): void
    {
        $targetVariableName = $this->getVariableName($assignNode->var);
        if (!$targetVariableName) {
            return;
        }

        $assignedExpr = $assignNode->expr;

        // 1. Initial Taint Sources
        if ($assignedExpr instanceof ArrayDimFetch &&
            $assignedExpr->var instanceof Variable &&
            is_string($assignedExpr->var->name) &&
            in_array($assignedExpr->var->name, ['_GET', '_POST', '_REQUEST']) &&
            $assignedExpr->dim instanceof String_
        ) {
            $sourceKey = $assignedExpr->dim->value;
            $superGlobalName = $assignedExpr->var->name;
            $this->taintState->addTaintedVariable($targetVariableName, "{$superGlobalName}['{$sourceKey}']");
            return;
        }

        if ($assignedExpr instanceof FuncCall &&
            $assignedExpr->name instanceof Node\Name &&
            strtolower($assignedExpr->name->toString()) === 'file_get_contents' &&
            isset($assignedExpr->getArgs()[0]) &&
            $assignedExpr->getArgs()[0]->value instanceof String_ &&
            $assignedExpr->getArgs()[0]->value->value === 'php://input'
        ) {
            $this->taintState->addTaintedVariable($targetVariableName, 'file_get_contents(\'php://input\')');
            return;
        }

        // 2. Taint Propagation
        // Propagation: Direct variable assignment ($a = $b;)
        if ($assignedExpr instanceof Variable) {
            $sourceVariableName = $this->getVariableName($assignedExpr);
            if ($sourceVariableName && $this->taintState->isVariableTainted($sourceVariableName)) {
                $originalSources = $this->taintState->getTaintSources($sourceVariableName);
                foreach ($originalSources as $originalSource) {
                     $this->taintState->addTaintedVariable($targetVariableName, "propagated from \${$sourceVariableName} (source: {$originalSource})");
                }
            }
            return;
        }

        // Propagation: String concatenation ($c = $a . 'suffix'; etc.)
        if ($assignedExpr instanceof Concat) {
            $concatTaintedSources = [];
            $this->collectTaintFromConcatParts($assignedExpr, $concatTaintedSources);

            foreach (array_unique($concatTaintedSources) as $propSource) {
                 $this->taintState->addTaintedVariable($targetVariableName, $propSource);
            }
            return;
        }
    }
}
