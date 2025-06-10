<?php

namespace Project\PhpDeserializeAnalyzer;

class User_TC001 {
    public $name;

    public function __construct($name) {
        $this->name = $name;
    }
}

// Simulate tainted input from $_GET
// In a real scenario, Psalm would see $_GET directly.
// For testing, we might need to adjust how taint is introduced or use annotations if $_GET isn't live.
// However, we expect Psalm to recognize $_GET as a source.
$data = $_GET['input_xss']; // Example: O:31:"Project\PhpDeserializeAnalyzer\User_TC001":1:{s:4:"name";s:20:"<script>alert(1)</script>";}

$obj = unserialize($data);

if ($obj instanceof User_TC001) {
    echo $obj->name; // Sink for 'html' taint
}

?>
