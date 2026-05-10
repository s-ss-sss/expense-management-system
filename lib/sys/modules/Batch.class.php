<?php

class Batch {

	private $today, $dao;

	/**
	 * コンストラクタ
	 *
	 * @access	public
	 * @param	$today, $dao
	 * @return
	 */
	public function __construct($today, $dao) {
		$this->today	= $today;
		$this->dao		= $dao;
	}

	/**
	 * バッチ処理実行
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function run() {

		// ログ開始
		$start_time = microtime(true);
		$this->_log('Batch Start');

		// トランザクション開始
		$this->dao->BeginTrans();

		try {
			// デモデータ削除
			$this->dao->deleteDemoData();
			$this->_log('Delete Demo Data Done');

			// デモデータ再投入
			$result = $this->dao->insertDemoSeedData($this->today);
			$this->_log("Insert Users {$result['users']} rows");
			$this->_log("Insert Types {$result['types']} rows");
			$this->_log("Insert Routes {$result['routes']} rows");
			$this->_log("Insert Expenses {$result['expenses']} rows");

			// トランザクション成功
			$this->dao->CommitTrans();

			// ログ終了
			$execution_time = round(microtime(true) - $start_time, 3);
			$this->_log("Batch End ({$execution_time}s)");

		} catch (Exception $e) {

			// トランザクション失敗
			$this->dao->RollbackTrans();
			error_log('[Batch Exception] ' . $e->getMessage(), 3, PHP_ERROR_LOG);
			throw $e;
		}
	}

	/**
	 * ログ出力
	 *
	 * @access	public
	 * @param	$message
	 * @return
	 */
	private function _log($message) {

		// ログ出力ファイル
		$file = ROOT_PATH . '/applogs/batch.log';

		// ログ出力メッセージ
		$log = '[' . date('Y-m-d H:i:s') . "] {$message}" . PHP_EOL;

		// 画面出力
		echo $log;

		// ファイル出力
		file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
	}
}
