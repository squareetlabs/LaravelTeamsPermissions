<?php

use Squareetlabs\LaravelTeamsPermissions\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest global test configuration
|--------------------------------------------------------------------------
|
| Aplicamos TestCase globalmente a todos los tests dentro de tests/Feature
| y tests/Unit. Esto evita que pest.php se incluya como test y mantiene
| el comportamiento consistente en todas las versiones de Laravel.
|
*/

uses(TestCase::class)->in('Feature', 'Unit');