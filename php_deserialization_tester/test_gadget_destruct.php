<?php

class Gadget {
    /**
     * @psalm-taint-source shell $command
     * @var string
     */
    public $command;

    // Constructor is not strictly needed for deserialization gadgets usually,
    // but can be there. Properties are set directly from the serialized string.
    // public function __construct($command = 'id') {
    //     $this->command = $command;
    // }

    public function __destruct() {
        // This is the sink
        system($this->command);
    }
}

// Simulate tainted input from GET request
$user_input = $_GET['payload'];

// The actual deserialization.
// If the payload is e.g., 'O:6:"Gadget":1:{s:7:"command";s:2:"ls";}',
// then $object->command will be 'ls'.
$object = unserialize($user_input);

// For testing, explicitly unset to trigger __destruct if script ends too soon
// or to make the call point clearer for analysis.
// In many real scenarios, __destruct is called at script shutdown.
// unset($object);

echo "Object deserialized. Destructor will run with command: " . (is_object($object) && property_exists($object, 'command') ? $object->command : 'N/A');

?>
