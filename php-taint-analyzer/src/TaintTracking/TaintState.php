<?php

namespace PhpTaintAnalyzer\TaintTracking;

class TaintState
{
    private array $taintedVariables = [];

    /**
     * Marks a variable as tainted.
     *
     * @param string $variableName The name of the variable.
     * @param string $source The source of the taint (e.g., '$_GET['key']', 'propagated from $sourceVar', 'concatenated from $var1, $var2').
     */
    public function addTaintedVariable(string $variableName, string $source): void
    {
        if (!isset($this->taintedVariables[$variableName])) {
            $this->taintedVariables[$variableName] = [];
        }
        // Avoid duplicate source entries for the same variable if re-processed
        if (!in_array($source, $this->taintedVariables[$variableName])) {
            $this->taintedVariables[$variableName][] = $source;
        }
        // For debugging purposes during development:
        // echo "Taint updated: Variable '{$variableName}', Source: '{$source}'\n";
    }

    /**
     * Checks if a variable is tainted.
     *
     * @param string $variableName The name of the variable.
     * @return bool True if tainted, false otherwise.
     */
    public function isVariableTainted(string $variableName): bool
    {
        return isset($this->taintedVariables[$variableName]) && !empty($this->taintedVariables[$variableName]);
    }

    /**
     * Gets the sources of taint for a variable.
     *
     * @param string $variableName The name of the variable.
     * @return array An array of taint sources, or an empty array if not tainted.
     */
    public function getTaintSources(string $variableName): array
    {
        return $this->taintedVariables[$variableName] ?? [];
    }

    /**
     * Gets all tainted variables and their sources.
     *
     * @return array An associative array where keys are variable names
     *               and values are arrays of taint sources.
     */
    public function getAllTaintedVariables(): array
    {
        return $this->taintedVariables;
    }
}
