<?php 

if(version_compare(phpversion(), '7.1', '<')){
    exit(0);
}

file_put_contents('tests/BaseTestCase.php', str_replace('public function setUp()', 'public function setUp(): void', file_get_contents('tests/BaseTestCase.php')));
file_put_contents('tests/BaseTestCase.php', str_replace('public function tearDown()', 'public function tearDown(): void', file_get_contents('tests/BaseTestCase.php')));
