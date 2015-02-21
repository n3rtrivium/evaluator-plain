<?php
// bootstrap.php
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

require_once "vendor/autoload.php";

$entityManager = false;
$parametesContent = @file_get_contents(__DIR__.'/parameters.yml');
$parametes = array();
if ($parametesContent == false) {
	printf("Unable to read %s\nCopy and modify parameters.yml.dist!\n", __DIR__.'/parameters.yml');
} else {
	$yaml = new Parser();
	try {
		$parametes = $yaml->parse($parametesContent);
	} catch (ParseException $e) {
		printf("Unable to parse the YAML string: %s\n", $e->getMessage());
	}
	
	if (!array_key_exists('connection', $parametes)) {
		printf("Unable to find connection in parameters.yml\n");
	} else {
		// obtaining the database handle
		$dbh = new \PDO('mysql:host=localhost;dbname='.$parametes['connection']['dbname'], $parametes['connection']['user'], $parametes['connection']['password']);
	}
}

if (!$dbh) {
	die("Database handle not created!\n");
}
