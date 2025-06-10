<?php

namespace PhpTaintAnalyzer\Parser;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class PhpParserWrapper
{
    private $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForHostVersion();
    }

    public function parse(string $code): ?array
    {
        try {
            return $this->parser->parse($code);
        } catch (Error $error) {
            // Handle parsing errors (e.g., log them)
            echo "Parse error: {$error->getMessage()}\n";
            return null;
        }
    }
}
