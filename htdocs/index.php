<?php

$root_path = realpath(__DIR__ . '/..');
require_once $root_path . '/lib/sys/common.php';
initApp($root_path);

require_once ROOT_PATH . '/lib/sys/controllers/expense.php';
