<?php

/**
 * 共通初期化
 *
 * @access	public
 * @param	$root_path
 * @return
 */
function initApp($root_path) {
	global $smarty;

	// エラーコード出力
	ini_set('display_errors', 1);
	error_reporting(E_ALL);

	// タイムゾーン設定
	date_default_timezone_set('Asia/Tokyo');

	// ROOT_PATH定義
	define('ROOT_PATH', $root_path);

	// 共通設定読み込み
	require_once ROOT_PATH . '/lib/conf/Common.conf.php';

	// エラーログファイル定義
	define('PHP_ERROR_LOG', BASE_DIR . 'applogs/error.log');

	// カスタムエラーハンドラ
	set_error_handler(function ($errno, $errstr, $errfile, $errline) {
		global $smarty;

		// ログ用メッセージ
		$error_message = sprintf('[Error %d] %s in %s on line %d', $errno, $errstr, $errfile, $errline);

		switch ($errno) {

			// Smarty出力
			case E_USER_WARNING:
				$smarty->append('warning', $errstr);
				error_log($error_message, 3, PHP_ERROR_LOG);
				break;

			// NOTICEはスルー
			case E_NOTICE:
			case E_USER_NOTICE:
				break;

			// その他はログ出力
			default:
				error_log($error_message, 3, PHP_ERROR_LOG);
				break;
		}
		return true;
	});

	// DB接続
	initDB();

	// セッション開始
	session_start();

	// Smarty初期化
	initSmarty();

	// Smartyに渡す共通変数
	$smarty->assign([
		'BASE_URL'	=> BASE_URL,
		'SITE_NAME'	=> SITE_NAME,
		'user_name'	=> $_SESSION['expense']['UserName'] ?? null
	]);

	// サニタイズ処理
	$_GET		= sanitize($_GET);
	$_POST		= sanitize($_POST);
	$_COOKIE	= sanitize($_COOKIE);
}

/**
 * DB初期化
 *
 * @access	public
 * @param
 * @return
 */
function initDB() {
	global $db;
	require_once(BASE_DIR . 'packages/adodb5/adodb.inc.php');
	$db = NewADOConnection('mysqli');
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);
	$db->Execute("SET NAMES utf8mb4");
}

/**
 * Smarty初期化
 *
 * @access	public
 * @param
 * @return
 */
function initSmarty() {
	global $smarty;
	require_once BASE_DIR . '/packages/smarty/libs/Smarty.class.php';
	$smarty = new Smarty();
	$smarty->setTemplateDir(BASE_DIR . '/lib/templates/');
	$smarty->setCompileDir(BASE_DIR . '/lib/templates_c/');
	$smarty->default_modifiers = ['escape:"html"'];
}

/**
 * サニタイズ処理
 *
 * @access	public
 * @param	$o
 * @return
 */
function sanitize($o) {
	if (is_array($o)) {
		return array_map('sanitize', $o);
	}
	return str_replace('\0', '', $o);
}

/**
 * パス情報を配列で取得
 *
 * @access	public
 * @param	$index = null
 * @return	is_numeric($index) ? ($path[$index] ?? null) : $path
 */
function getPathInfo($index = null) {
	$path = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
	return is_numeric($index) ? ($path[$index] ?? null) : $path;
}

/**
 * PATH_INFOの階層チェック
 *
 * @access	public
 * @param	$path_level
 * @return
 */
function validPathInfo($path_level) {
	if (count(getPathInfo()) != $path_level) {
		header('HTTP/1.1 404 Not Found');
		echo '404 Not Found';
		exit;
	}
}

/**
 * 全角スペースも含めたtrim
 *
 * @access	public
 * @param	$str
 * @return
 */
function trimFull($str) {
	return preg_replace('/^[\s　]+|[\s　]+$/u', '', $str ?? '');
}
