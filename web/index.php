<?php

use N3rtrivium\EvaluatorPlain\App;

require_once "../bootstrap.php";

$app = new App($dbh);
$app->handle();