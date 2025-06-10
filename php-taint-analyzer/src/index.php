<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpTaintAnalyzer\Parser\PhpParserWrapper;
use PhpTaintAnalyzer\TaintTracking\TaintTrackingVisitor;
use PhpTaintAnalyzer\TaintTracking\TaintState;
use PhpParser\NodeTraverser;

// Example PHP code to analyze
$code = <<<'CODE'
<?php
// --- Initial Taint Sources ---
$username = $_GET['user'];          // Source: _GET
$password = $_POST['pass'];         // Source: _POST
$sessionId = $_REQUEST['sid'];      // Source: _REQUEST
$rawData = file_get_contents('php://input'); // Source: php://input

$normalVar = "some_safe_string";

// --- Taint Propagation: Direct Assignment ---
$propagated_username = $username;                 // $username is tainted
$propagated_normal = $normalVar;                  // $normalVar is not tainted
$propagated_twice = $propagated_username;         // $propagated_username is now tainted

// --- Taint Propagation: String Concatenation ---
$concat_rhs = $password . " :appended_string";    // $password is tainted
$concat_lhs = "prepended_string: " . $sessionId;  // $sessionId is tainted
$concat_both = $username . $password;             // Both $username and $password are tainted
$concat_tainted_and_normal = $rawData . $normalVar; // $rawData is tainted, $normalVar is not
$concat_normal_and_tainted = $normalVar . $username; // $normalVar is not, $username is

// --- Overwriting / Reassignment Test ---
$reassigned_var = $_GET['reassign_test']; // Initial taint
$reassigned_var = "now_a_safe_string";  // Reassigned (current logic should retain original taint if not cleared)
                                        // More advanced logic would clear taint here or handle it differently.
                                        // For now, we expect it to remain tainted from _GET.

$another_safe = "completely_safe";
$complex_concat = "prefix_" . $username . "_middle_" . $password . "_" . $another_safe . "_suffix";

echo "--- Outputting variables (simulating usage, sinks not yet implemented) ---";
echo $username;
echo $password;
echo $sessionId;
echo $rawData;
echo $normalVar;
echo $propagated_username;
echo $propagated_normal;
echo $propagated_twice;
echo $concat_rhs;
echo $concat_lhs;
echo $concat_both;
echo $concat_tainted_and_normal;
echo $concat_normal_and_tainted;
echo $reassigned_var; // Will show tainted by _GET
echo $complex_concat;

CODE;

echo "--- Parsing and Traversing AST ---\n";

$parser = new PhpParserWrapper();
$taintState = new TaintState();
$visitor = new TaintTrackingVisitor($taintState);

$ast = $parser->parse($code);

if ($ast) {
    $traverser = new NodeTraverser();
    $traverser->addVisitor($visitor);
    $traverser->traverse($ast);

    echo "\n--- Taint Analysis Results ---\n";
    $allTainted = $taintState->getAllTaintedVariables();

    if (empty($allTainted)) {
        echo "No tainted variables found.\n";
    } else {
        echo "Tainted variables and their sources:\n";
        foreach ($allTainted as $varName => $sources) {
            echo "- Variable: $" . $varName . "\n";
            foreach (array_unique($sources) as $source) { // Ensure unique sources are printed
                echo "  Source: " . $source . "\n";
            }
        }
    }
} else {
    echo "Failed to parse the code.\n";
}

echo "\n--- End of Analysis ---\n";
