<?php

// .envファイル
$env_path = ROOT_PATH . '/.env';

// .envファイルの存在チェック
if (file_exists($env_path)) {

	// .envの中身を配列化
	$lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	// 配列を1行ずつ処理
	foreach ($lines as $line) {

		// コメント行をスキップ
		if ($line === '' || strpos(trim($line), '#') === 0) {
			continue;
		}

		// 配列化してgetenvに環境変数として登録
		if (strpos($line, '=') !== false) {
			list($key, $value) = explode('=', $line, 2);
			putenv(trim($key) . '=' . trim($value));
		}
	}
}

// BASE
define('BASE_URL',				'https://dolzap.conohawing.com/expense/');
define('BASE_DIR',				ROOT_PATH . '/');

// DB
define('DB_HOST',				getenv('DB_HOST'));
define('DB_USER',				getenv('DB_USER'));
define('DB_PASS',				getenv('DB_PASS'));
define('DB_DATABASE',			getenv('DB_DATABASE'));
define('DB_PORT',				getenv('DB_PORT'));

// MAIL
define('MAIL_FROM', 			getenv('MAIL_FROM'));
define('MAIL_DEMO', 			getenv('MAIL_DEMO'));
define('MAIL_ADMIN', 			getenv('MAIL_ADMIN'));

// SITE
define('SITE_NAME', 			'旅費請求システム');

// ERROR：出力
define('ERROR_USER_INVALID',	'データが不正です');
define('ERROR_USER_CSRF',		'セッションが無効になりました');
define('ERROR_USER_WARNING',	'実行に失敗しました');
define('ERROR_USER_DEMO',		'デモユーザーはこの操作を実行できません');

// ERROR：ログ
define('LOG_NO_TARGET',			'対象のデータが存在しません');
define('LOG_SQL_FAILED',		'SQLの実行に失敗しました');
define('LOG_UNEXPECTED_COUNT',	'処理の件数が想定外です');
define('LOG_CSRF_FAILED',		'CSRFトークン検証に失敗しました');
define('LOG_MAIL_FAILED',		'メール送信に失敗しました');
define('LOG_DEMO_FAILED',		'デモユーザーの操作が失敗しました');

// PASSWORD
define('DEMO_PASSWORD',			getenv('DEMO_PASSWORD'));
define('ADMIN_PASSWORD',		getenv('ADMIN_PASSWORD'));
