<?php

class BatchDao {

	private $db;

	/**
	 * コンストラクタ：DBアクセス
	 *
	 * @access	public
	 * @param	$db
	 * @return
	 */
	public function __construct($db) {
		$this->db = $db;
	}

	// ============================================================
	// 共通処理
	// ============================================================

	/**
	 * トランザクション開始
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function BeginTrans() {
		$this->db->BeginTrans();
	}

	/**
	 * コミット
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function CommitTrans() {
		$this->db->CommitTrans();
	}

	/**
	 * ロールバック
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function RollbackTrans() {
		$this->db->RollbackTrans();
	}

	// ============================================================
	// デモデータ削除
	// ============================================================

	/**
	 * デモデータ削除
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function deleteDemoData() {

		// 外部キー無効化
		$this->db->Execute('SET FOREIGN_KEY_CHECKS = 0');

		// 削除対象テーブル
		$tables = [
			't_courses',			// よく使うコースデータ
			't_expenses',			// 請求データ
			't_users',				// ユーザーデータ
			't_mail_recipients',	// メール宛先データ
			't_types',				// 種別データ
			't_routes',				// 路線データ
		];

		try {

			// 削除実行
			foreach ($tables as $table) {
				if ($this->db->Execute("TRUNCATE TABLE {$table}") === false) {
					throw new Exception("Failed to truncate {$table}");
				}
			}

		} finally {

			// 外部キー有効化
			$this->db->Execute('SET FOREIGN_KEY_CHECKS = 1');
		}
	}

	// ============================================================
	// デモデータ再投入
	// ============================================================

	/**
	 * デモデータ再投入
	 *
	 * @access	public
	 * @param	$today
	 * @return	array
	 */
	public function insertDemoSeedData($today) {

		// デモデータ投入
		$routes				= $this->_insertRoutes();			// 路線データ
		$types				= $this->_insertTypes();			// 種別データ
		$mail_recipients	= $this->_insertMailRecipients();	// メール宛先データ
		$users				= $this->_insertUsers();			// ユーザーデータ
		$expenses			= $this->_insertExpenses($today);	// 請求データ
		$courses			= $this->_insertCourses();			// よく使うコースデータ

		return [
			'routes'			=> $routes,
			'types'				=> $types,
			'mail_recipients'	=> $mail_recipients,
			'users'				=> $users,
			'expenses'			=> $expenses,
			'courses'			=> $courses,
		];
	}

	/**
	 * 路線データ投入
	 *
	 * @access	private
	 * @param
	 * @return	$count
	 */
	private function _insertRoutes() {

		// 投入する路線データ
		$routes = [
			['地下鉄', 1],
			['バス', 2],
			['その他', 3],
		];

		// SQL文
		$sql = '
			INSERT INTO t_routes (
				route_name, sort_order
			) VALUES (
				?, ?
			)
		';

		// データ投入
		$count = 0;
		foreach ($routes as $route) {
			if ($this->db->Execute($sql, $route) === false) {
				throw new Exception('Failed to insert route');
			}
			$count++;
		}

		// 投入件数を返す
		return $count;
	}

	/**
	 * 種別データ投入
	 *
	 * @access	private
	 * @param
	 * @return	$count
	 */
	private function _insertTypes() {

		// 投入する種別データ
		$types = [
			['片道', 1],
			['往復', 2],
			['その他', 3],
		];

		// SQL文
		$sql = '
			INSERT INTO t_types (
				type_name, sort_order
			) VALUES (
				?, ?
			)
		';

		// データ投入
		$count = 0;
		foreach ($types as $type) {
			if ($this->db->Execute($sql, $type) === false) {
				throw new Exception('Failed to insert type');
			}
			$count++;
		}

		// 投入件数を返す
		return $count;
	}

	/**
	 * メール宛先データ投入
	 *
	 * @access	private
	 * @param
	 * @return	$count
	 */
	private function _insertMailRecipients() {

		// 投入する路線データ
		$mail_recipients = [
			[MAIL_ADMIN, 1],
		];

		// SQL文
		$sql = '
			INSERT INTO t_mail_recipients (
				email, sort_order
			) VALUES (
				?, ?
			)
		';

		// データ投入
		$count = 0;
		foreach ($mail_recipients as $mail_recipient) {
			if ($this->db->Execute($sql, $mail_recipient) === false) {
				throw new Exception('Failed to insert mail recipient');
			}
			$count++;
		}

		// 投入件数を返す
		return $count;
	}

	/**
	 * ユーザーデータ投入
	 *
	 * @access	private
	 * @param
	 * @return	$count
	 */
	private function _insertUsers() {

		// 投入するユーザーデータ
		$users = [
			['デモユーザー', MAIL_DEMO, password_hash(DEMO_PASSWORD, PASSWORD_DEFAULT), 1],
			['管理ユーザー', MAIL_ADMIN, password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT), 1],
		];

		// SQL文
		$sql = '
			INSERT INTO t_users (
				name, email, password, is_admin
			) VALUES (
				?, ?, ?, ?
			)
		';

		// データ投入
		$count = 0;
		foreach ($users as $user) {
			if ($this->db->Execute($sql, $user) === false) {
				throw new Exception('Failed to insert user');
			}
			$count++;
		}

		// 投入件数を返す
		return $count;
	}

	/**
	 * 請求データ投入
	 *
	 * @access	private
	 * @param	$today
	 * @return	$count
	 */
	private function _insertExpenses($today) {

		// 投入する請求データ
		$expenses = [
			[1, $today->modify('-1 day')->format('Y-m-d'), 1, 2, '栄', '名古屋', 420, 'ミッドランドスクエア'],
			[1, $today->modify('-3 day')->format('Y-m-d'), 2, 2, '豊田市', 'トヨタ記念病院', 620, 'トヨタ記念病院'],
			[2, $today->modify('-5 day')->format('Y-m-d'), 1, 1, '名古屋', '伏見', 210, '名古屋市科学館'],
		];

		// SQL文
		$sql = '
			INSERT INTO t_expenses (
				user_id, purchase_date, route_id, type_id,
				section_from, section_to, fee, note
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?
			)
		';

		// データ投入
		$count = 0;
		foreach ($expenses as $expense) {
			if ($this->db->Execute($sql, $expense) === false) {
				throw new Exception('Failed to insert expense');
			}
			$count++;
		}

		// 投入件数を返す
		return $count;
	}

	/**
	 * よく使うコースデータ投入
	 *
	 * @access	private
	 * @param
	 * @return	$count
	 */
	private function _insertCourses() {

		// 投入するよく使うコースデータ
		$courses = [
			[1, '名古屋往復', 1, 2, '栄', '名古屋', 420, ''],
		];

		// SQL文
		$sql = '
			INSERT INTO t_courses (
				user_id, course_name, route_id, type_id,
				section_from, section_to, fee, note
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?
			)
		';

		// データ投入
		$count = 0;
		foreach ($courses as $course) {
			if ($this->db->Execute($sql, $course) === false) {
				throw new Exception('Failed to insert course');
			}
			$count++;
		}

		// 投入件数を返す
		return $count;
	}
}
