<?php

class MyClass {
    public $data;
    public function __construct($data) {
        $this->data = $data;
    }
}

// Simulate tainted input from GET request
$tainted_string = $_GET['input'];

$object = unserialize($tainted_string);

// This would be a sink in a real scenario
// For now, we just want to see if $object is tainted
echo $object->data;

?>
