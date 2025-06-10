<?php

namespace Project\PhpDeserializeAnalyzer;

class Command_TC002 {
    public $cmd;

    public function __construct($cmd) {
        $this->cmd = $cmd;
    }

    public function __destruct() {
        // Vulnerability: calling system() with tainted data
        system($this->cmd); // Sink for 'shell' taint
    }
}

// Simulate tainted input
$data = $_GET['input_rce']; // Example: O:33:"Project\PhpDeserializeAnalyzer\Command_TC002":1:{s:3:"cmd";s:2:"id";}

$obj = unserialize($data);

// Object goes out of scope here, __destruct is called
?>
