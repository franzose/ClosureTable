<?php
// This file patches test case files containing setUp and tearDown methods.
// It's a hacky workaround needed until we drop support of PHP < 7.2.
// Cause: newer PHPUnit has added typehints to its setUp and tearDown methods
// but ClosureTable 6.x must support PHP 5.6-7.0 as well,
// so we cannot use typehints by default.

if (PHP_VERSION_ID < 70100) {
    exit(0);
}

require_once './vendor/autoload.php';

use Symfony\Component\Finder\Finder;

$finder = new Finder();
$finder->files()
    ->ignoreVCS(false)
    ->in(__DIR__)
    ->name('*.php')
    ->notName('script-change-testcase-return-type.php')
    ->contains('use Orchestra\Testbench\TestCase;');

foreach ($finder as $file) {
    $path = $file->getRealPath();
    $contents = file_get_contents($path);
    $contents = str_replace(
        ['public function setUp()', 'public function tearDown()'],
        ['public function setUp(): void', 'public function tearDown(): void'],
        $contents
    );

    file_put_contents($path, $contents);
}