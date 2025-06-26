<?php

// We manually include the entry point of the Composer autoloader.
// This is what PHPUnit is supposed to do via its bootstrap file.
require __DIR__.'/vendor/autoload.php';

echo "SUCCESS: vendor/autoload.php was included.\n";

try {
    // We try to instantiate the class that PHPUnit cannot find.
    $money = \Brick\Money\Money::of(10, 'EUR');
    
    // If we reach this line, it means the class was found and everything works.
    echo "SUCCESS: The class Brick\\Money\\Money was found and instantiated correctly!\n";

} catch (\Throwable $e) {
    // If we enter here, it means the class was not found.
    echo "ERROR: Failed to find or instantiate the class.\n";
    echo "Error message: " . $e->getMessage() . "\n";
}