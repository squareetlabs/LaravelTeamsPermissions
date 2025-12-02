<?php

use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;

// Pest 1.x uses uses(), Pest 2.x+ uses pest()
if (function_exists('pest')) {
    // Pest 2.x and 3.x syntax
    pest()
        ->extend(TestCase::class)
        ->in(__DIR__);
} else {
    // Pest 1.x syntax
    uses(TestCase::class)->in(__DIR__);
}
