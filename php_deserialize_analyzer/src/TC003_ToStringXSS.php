<?php

namespace Project\PhpDeserializeAnalyzer;

class WebContent_TC003 {
    public $html;

    public function __construct($html) {
        $this->html = $html;
    }

    public function __toString() {
        return $this->html;
    }
}

// Simulate tainted input
$data = $_GET['input_string_xss']; // Example: O:36:"Project\PhpDeserializeAnalyzer\WebContent_TC003":1:{s:4:"html";s:20:"<h1>PoC</h1><script>alert(2)</script>";}

$obj = unserialize($data);

if ($obj instanceof WebContent_TC003) {
    echo $obj; // __toString() is called, sink for 'html' taint
}

?>
