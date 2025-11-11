<?php
require_once __DIR__ . '/../src/helpers.php';

function assertEqual($actual, $expected, $message)
{
    if ($actual !== $expected) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
}

$input = "  <b>Hello</b> ";
$sanitized = sanitize($input);
assertEqual($sanitized, "Hello", "sanitize() should trim and remove tags");

echo "All tests passed!\n";
exit(0);
