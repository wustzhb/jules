<?php

// --- Class Definitions for the Gadget Chain ---

class ActionPerformer {
    /**
     * @psalm-taint-source file $filename
     * @var string
     */
    public $filename = '/tmp/default.txt'; // Default filename

    /**
     * The sink method.
     * @param string $content_to_write
     */
    public function execute($content_to_write) {
        echo "ActionPerformer: Attempting to write to: " . $this->filename . " with content: " . $content_to_write . "\n";
        file_put_contents($this->filename, $content_to_write);
        echo "ActionPerformer: Write operation completed.\n";
    }
}

class MiddleMan {
    public $action_agent; // Will be an instance of ActionPerformer
    /**
     * @psalm-taint-source input $data_for_action
     * @var string
     */
    public $data_for_action = 'default data'; // Default data

    public function __construct() {
        // Ensure action_agent is initialized if not set by deserialization,
        // though for a gadget, it's typically set.
        if (!isset($this->action_agent)) {
            $this->action_agent = new ActionPerformer();
        }
    }

    public function process() {
        echo "MiddleMan: Processing...\n";
        if (isset($this->action_agent) && method_exists($this->action_agent, 'execute')) {
            $this->action_agent->execute($this->data_for_action);
        } else {
            echo "MiddleMan: Action agent not configured or execute method missing.\n";
        }
    }
}

class EntryPoint {
    public $handler; // Will be an instance of MiddleMan

    public function __construct() {
        // Ensure handler is initialized if not set by deserialization.
        if (!isset($this->handler)) {
            $this->handler = new MiddleMan();
        }
    }

    public function __destruct() {
        echo "EntryPoint: __destruct called.\n";
        if (isset($this->handler) && method_exists($this->handler, 'process')) {
            $this->handler->process();
        } else {
            echo "EntryPoint: Handler not configured or process method missing.\n";
        }
    }
}

// --- Test Script ---

echo "Script starting.\n";

if (isset($_GET['payload'])) {
    $tainted_payload = $_GET['payload'];
    echo "Received payload: " . htmlspecialchars($tainted_payload) . "\n";

    // Attempt to deserialize the payload
    // A real payload would be crafted to set $filename in ActionPerformer
    // and $data_for_action in MiddleMan.
    // Example to manually create a payload (for testing the classes, not for Psalm input):
    /*
    $ap = new ActionPerformer();
    $ap->filename = '/tmp/exploit.txt';

    $mm = new MiddleMan();
    $mm->action_agent = $ap;
    $mm->data_for_action = ' सक्सेसफुल exploitation! '; // some unicode to test

    $ep = new EntryPoint();
    $ep->handler = $mm;

    $serialized_payload = serialize($ep);
    echo "Example serialized payload (URL encode this for GET):\n";
    echo htmlspecialchars($serialized_payload) . "\n";
    // For GET: O%3A10%3A%22EntryPoint%22%3A1%3A%7Bs%3A7%3A%22handler%22%3BO%3A9%3A%22MiddleMan%22%3A2%3A%7Bs%3A12%3A%22action_agent%22%3BO%3A15%3A%22ActionPerformer%22%3A1%3A%7Bs%3A8%3A%22filename%22%3Bs%3A16%3A%22%2Ftmp%2Fexploit.txt%22%3B%7Ds%3A15%3A%22data_for_action%22%3Bs%3A29%3A%22+%E0%A4%B8%E0%A4%AB%E0%A4%B2%E0%A4%A4%E0%A4%BE%E0%A4%AA%E0%A5%82%E0%A4%B0%E0%A5%8D%E0%A4%B5%E0%A4%95+exploitation%21+%22%3B%7D%7D
    */

    $object = unserialize($tainted_payload);

    // Optional: Explicitly trigger __destruct for easier testing if needed,
    // otherwise it happens at script shutdown.
    // unset($object);

    echo "Deserialization attempted. Object: ";
    var_dump($object); // See what we got
} else {
    echo "No payload provided. Use ?payload=<serialized_string>\n";
    echo "Example to trigger default behavior (writes to /tmp/default.txt):\n";
    // This payload just creates the objects, relying on default values or constructor init.
    $ep_default = new EntryPoint(); // Will use default ActionPerformer and MiddleMan
    echo htmlspecialchars(serialize($ep_default)) . "\n";
    // O%3A10%3A%22EntryPoint%22%3A1%3A%7Bs%3A7%3A%22handler%22%3BO%3A9%3A%22MiddleMan%22%3A2%3A%7Bs%3A12%3A%22action_agent%22%3BO%3A15%3A%22ActionPerformer%22%3A1%3A%7Bs%3A8%3A%22filename%22%3Bs%3A15%3A%22%2Ftmp%2Fdefault.txt%22%3B%7Ds%3A15%3A%22data_for_action%22%3Bs%3A12%3A%22default+data%22%3B%7D%7D
    // If you pass this, it will call __destruct on $ep_default when script ends.
    // To test the unserialize path with this:
    // ?payload=O%3A10%3A%22EntryPoint%22%3A1%3A%7Bs%3A7%3A%22handler%22%3BO%3A9%3A%22MiddleMan%22%3A2%3A%7Bs%3A12%3A%22action_agent%22%3BO%3A15%3A%22ActionPerformer%22%3A1%3A%7Bs%3A8%3A%22filename%22%3Bs%3A15%3A%22%2Ftmp%2Fdefault.txt%22%3B%7Ds%3A15%3A%22data_for_action%22%3Bs%3A12%3A%22default+data%22%3B%7D%7D
    // This will make $object = unserialize(...) create the chain with default values.
}

echo "Script ending.\n";

?>
