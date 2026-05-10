<?php

// CLI専用
if (php_sapi_name() !== 'cli') {
	exit('CLI only');
}

// 初期設定
$root_path = realpath(__DIR__ . '/../../..');
require_once $root_path . '/lib/sys/common.php';
initApp($root_path);

// 実行日
$today = new DateTimeImmutable('today');

require_once ROOT_PATH . '/lib/sys/modules/daos/BatchDao.class.php';
require_once ROOT_PATH . '/lib/sys/modules/Batch.class.php';

$batchDao	= new BatchDao($db);
$batch		= new Batch($today, $batchDao);

// バッチ処理実行
$batch->run();
