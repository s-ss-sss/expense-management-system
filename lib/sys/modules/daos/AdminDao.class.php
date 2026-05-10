<?php

require_once 'CommonDao.class.php';

class AdminDao extends CommonDao {

	// コンストラクタ：DBアクセス
	public function __construct($db) {
		parent::__construct($db);
	}

	// ============================================================
	// 共通処理
	// ============================================================

	/**
	 * 路線の全件取得
	 *
	 * @access	public
	 * @param
	 * @return	array
	 */
	public function getRoutes() {

		// SQL文
		$sql = '
			SELECT
				r.id, r.route_name, r.sort_order, r.is_active
			FROM
				t_routes r
			WHERE
				r.is_active = 1
			ORDER BY
				r.sort_order ASC
		';

		// 1行ずつ配列に追加
		return $this->db->Execute($sql)->GetArray();
	}

	/**
	 * 種別の全件取得
	 *
	 * @access	public
	 * @param
	 * @return	array
	 */
	public function getTypes() {

		// SQL文
		$sql = '
			SELECT
				t.id, t.type_name, t.sort_order, t.is_active
			FROM
				t_types t
			WHERE
				t.is_active = 1
			ORDER BY
				t.sort_order ASC
		';

		// 1行ずつ配列に追加
		return $this->db->Execute($sql)->GetArray();
	}

	/**
	 * テーブルのsort_orderの最大値を取得
	 *
	 * @access	public
	 * @param	$id
	 * @return	array
	 */
	public function getMaxSortOrder($table) {

		// SQL文
		$sql = "
			SELECT
				COALESCE(MAX(sort_order), 0)
			FROM
				{$table}
			WHERE
				is_active = 1
		";

		// 実行
		return (int)$this->db->GetOne($sql);
	}

	// ============================================================
	// 請求データ管理
	// ============================================================

	/**
	 * 旅費請求データの全件取得
	 *
	 * @access	public
	 * @param
	 * @return	array
	 */
	public function getExpenses($from, $to, $user_id = '') {

		// 取得期間
		$where	= ["
			e.created_at >= ? AND
			e.created_at < DATE_ADD(?, INTERVAL 1 DAY)
		"];

		$params = [$from, $to];

		// ユーザーIDがある場合に追加
		if (! empty($user_id)) {
			$where[] 	= 'e.user_id = ?';
			$params[]	= $user_id;
		}

		// WHERE句作成
		$where_sql = implode(' AND ', $where);

		// SQL文
		$sql = "
			SELECT
				e.id, e.user_id, u.name AS user_name, e.purchase_date, e.route_id, e.type_id,
				e.section_from, e.section_to, e.fee, e.note, e.cancel_reason, e.is_active, e.created_at
			FROM
				t_expenses e
			LEFT JOIN
				t_users u ON e.user_id = u.id
			WHERE
				{$where_sql}
			ORDER BY
				e.user_id ASC,
				DATE(e.created_at) ASC,
				e.purchase_date ASC,
				e.id ASC
		";

		// 1行ずつ配列に追加
		return $this->db->Execute($sql, $params)->GetArray();
	}

	/**
	 * ユーザーデータの全件取得
	 *
	 * @access	public
	 * @param
	 * @return	array
	 */
	public function getAllUsers() {

		// SQL文
		$sql = '
			SELECT
				u.id, u.name
			FROM
				t_users u
			ORDER BY
				u.id ASC
		';

		// 1行ずつ配列に追加
		return $this->db->Execute($sql)->GetArray();
	}

	/**
	 * 旅費請求データの取得
	 *
	 * @access	public
	 * @param	$request_id
	 * @return	array
	 */
	public function getExpenseById($request_id) {

		// SQL文
		$sql = '
			SELECT
				e.id, e.user_id, u.name AS user_name, e.purchase_date, e.route_id, e.type_id,
				e.section_from, e.section_to, e.fee, e.note, e.cancel_reason, e.is_active, e.created_at
			FROM
				t_expenses e
			LEFT JOIN
				t_users u ON e.user_id = u.id
			WHERE
				e.id = ?
		';

		// 実行
		return $this->db->GetRow($sql, [$request_id]);
	}

	/**
	 * 取消更新
	 *
	 * @access	public
	 * @param	$request_id, $cancel_reason
	 * @return	array
	 */
	 public function updateExpenseCancel($request_id, $cancel_reason) {

		 // SQL文
	 	$sql = '
			UPDATE
				t_expenses
			SET
				cancel_reason	= ?,
				is_active		= 0,
				updated_at		= NOW()
			WHERE
				id = ?
		';

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// フォームデータ取得
			$params = [$cancel_reason, $request_id];

			// 実行
			$rs = $this->db->Execute($sql, $params);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// トランザクション成功
			$this->db->CommitTrans();
			return true;

		} catch (Exception $e) {

			// トランザクション失敗
			$this->db->RollbackTrans();
			throw $e;
		}
	 }

	/**
	 * CSVデータ取得
	 *
	 * @access	public
	 * @param
	 * @return  array
	 */
	public function getRequestCsv($from = null, $to = null, $user_id = null) {

		$where	= [];
		$params	= [];

		// 期間絞り込み（開始）
		if (! empty($from)) {
			$where[] 	= 'e.created_at >= ?';
			$params[]	= $from;
		}

		// 期間絞り込み（終了）
		if (! empty($to)) {
			$where[]	= "e.created_at < DATE_ADD(?, INTERVAL 1 DAY)"; // 翌日未満
			$params[]	= $to;
		}

		// ユーザーID絞り込み
		if (! empty($user_id)) {
			$where[]	= 'e.user_id = ?';
			$params[]	= $user_id;
		}

		// 条件があればWHERE句作成
		$where_sql = '';
		if (! empty($where)) {
			$where_sql = 'WHERE ' . implode(' AND ', $where);
		}

		// SQL文
		$sql = "
			SELECT
				e.id, e.user_id, u.name AS user_name, e.purchase_date, e.route_id, e.type_id,
				e.section_from, e.section_to, e.fee, e.note, e.cancel_reason, e.is_active, e.created_at
			FROM
				t_expenses e
			LEFT JOIN
				t_users u ON e.user_id = u.id
			{$where_sql}
			ORDER BY
				e.user_id ASC,
				DATE(e.created_at) ASC,
				e.purchase_date ASC,
				e.id ASC
		";

		return $this->db->GetAll($sql, $params);
	}

	// ============================================================
	// ユーザー管理
	// ============================================================

	/**
	 * ユーザーの全件取得
	 *
	 * @access	public
	 * @param
	 * @return	array
	 */
	public function getUsers() {

		// SQL文
		$sql = '
			SELECT
				u.id, u.name, u.email, u.is_admin, u.is_active
			FROM
				t_users u
			WHERE
				u.is_active = 1
			ORDER BY
				u.id ASC
		';

		// 1行ずつ配列に追加
		$rows = $this->db->Execute($sql)->GetArray();

		// 参照渡しでis_adminのラベルを変換
		foreach ($rows as &$row) {
			$row['is_admin'] = $row['is_admin'] == 1 ? '管理者' : '一般';
		}

		// 参照リセット
		unset($row);

		return $rows;
	}

	/**
	 * ユーザーの登録
	 *
	 * @access	public
	 * @param	$form_data
	 * @return	array
	 */
	public function insertUser($form_data) {

		// SQL文
		$sql = '
			INSERT INTO t_users (
				id, name, email, password, is_admin
			) VALUES (
				?, ?, ?, ?, ?
			)
		';

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// フォームデータ取得
			$params = [
				$form_data['user_id'],
				$form_data['name'],
				$form_data['email'],
				$form_data['password'],
				$form_data['is_admin'] == '1' ? 1 : 0
			];

			// 実行
			$rs = $this->db->Execute($sql, $params);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// トランザクション成功
			$this->db->CommitTrans();
			return true;

		} catch (Exception $e) {

			// トランザクション失敗
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * ユーザーのIDの重複チェック
	 *
	 * @access	public
	 * @param	$id
	 * @return	$count > 0
	 */
	public function isDuplicateId($id, $exclude_id = null) {

		// SQL文
		$sql = '
			SELECT COUNT(*)
			FROM
				t_users u
			WHERE
				u.id = ?
		';

		$params = [$id];

		if ($exclude_id != null) {
			$sql		.= ' AND id != ?';
			$params[]	= $exclude_id;
		}

		// 実行して一致するIDの数が1以上の場合はtrueを返す
		return $this->db->GetOne($sql, $params) > 0;
	}

	/**
	 * ユーザーのメールアドレスの重複チェック
	 *
	 * @access	public
	 * @param	$id
	 * @return	$count > 0
	 */
	public function isDuplicateEmail($email, $exclude_id = null) {

		// SQL文
		$sql = '
			SELECT COUNT(*)
			FROM
				t_users u
			WHERE
				u.email = ?
		';

		$params = [$email];

		if ($exclude_id != null) {
			$sql 		.= ' AND id != ?';
			$params[]	= $exclude_id;
		}

		// 実行して一致するIDの数が1以上の場合はtrueを返す
		return $this->db->GetOne($sql, $params) > 0;
	}

	/**
	 * ユーザーの更新
	 *
	 * @access	public
	 * @param	$id
	 * @return	array
	 */
	public function updateUser($edit_data, $original_id) {

		// トランザクション開始
		$this->db->BeginTrans();

		try {

			// パスワード入力がある場合
			if (! empty($edit_data['password'])) {

				// SQL文
				$sql = '
					UPDATE
						t_users
					SET
						id			= ?,
						name 		= ?,
						email		= ?,
						password	= ?,
						is_admin	= ?
					WHERE
						id = ?
				';

				// フォームデータ取得
				$params = [
					$edit_data['user_id'],
					$edit_data['name'],
					$edit_data['email'],
					$edit_data['password'],
					$edit_data['is_admin'] == '1' ? 1 : 0,
					$original_id
				];

			// パスワード入力がない場合
			} else {

				// SQL文
				$sql = '
					UPDATE
						t_users
					SET
						id			= ?,
						name 		= ?,
						email		= ?,
						is_admin	= ?
					WHERE
						id = ?
				';

				// フォームデータ取得
				$params = [
					$edit_data['user_id'],
					$edit_data['name'],
					$edit_data['email'],
					$edit_data['is_admin'] == '1' ? 1 : 0,
					$original_id
				];
			}

			// 実行
			$rs = $this->db->Execute($sql, $params);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// トランザクション成功
			$this->db->CommitTrans();
			return true;

		} catch (Exception $e) {

			// トランザクション失敗
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * ユーザーの削除
	 *
	 * @access	public
	 * @param	$user_ids
	 * @return
	 */
	public function deleteUsers($user_ids) {

		// プレースホルダを削除対象の数作成（例：?,?,?,...）
		$placeholders = implode(',', array_fill(0, count($user_ids), '?'));

		// SQL文
		$sql = "
			UPDATE
				t_users
			SET
				is_active 	= 0,
				updated_at 	= NOW()
			WHERE
				id IN ($placeholders)
		";

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// 実行
			$rs = $this->db->Execute($sql, $user_ids);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// トランザクション成功
			$this->db->CommitTrans();
			return true;

		} catch (Exception $e) {

			// トランザクション失敗
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * ユーザーの削除IDの存在チェック
	 *
	 * @access	public
	 * @param	$user_ids
	 * @return
	 */
	public function existsUserIds($user_ids) {

		// 配列が空ならfalse
		if (empty($user_ids)) return false;

		// プレースホルダを削除対象の数作成（例：?,?,?,...）
		$placeholders = implode(',', array_fill(0, count($user_ids), '?'));

		// SQL文
		$sql = "
			SELECT
				COUNT(*) AS cnt
			FROM
				t_users u
			WHERE
				u.id IN ($placeholders)
			AND
				u.is_active = 1
		";

		// 実行
		$rs = $this->db->GetRow($sql, $user_ids);

		// 選択したIDと存在するIDが一致すればtrue
		return (int)$rs['cnt'] == count($user_ids);
	}

	// ============================================================
	// メール宛先管理
	// ============================================================

	/**
	 * メールの登録
	 *
	 * @access	public
	 * @param	$form_data
	 * @return	array
	 */
	public function insertMail($form_data) {

		// SQL文
		$sql_sort_order = '
			SELECT
				COALESCE(MAX(m.sort_order), 0)
			FROM
				t_mail_recipients m
		';

		// SQL文
		$sql_insert = '
			INSERT INTO t_mail_recipients (
				email, sort_order
			) VALUES (
				?, ?
			)
		';

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// sort_orderの最大値の連番を作成
			$max		= $this->db->GetOne($sql_sort_order);
			$sort_order	= $max + 1;

			// 実行
			$rs = $this->db->Execute($sql_insert, [$form_data['email'], $sort_order]);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// トランザクション成功
			$this->db->CommitTrans();
			return true;

		} catch (Exception $e) {

			// トランザクション失敗
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * メールの重複チェック
	 *
	 * @access	public
	 * @param	$id
	 * @return	$count > 0
	 */
	public function isDuplicateRecipient($email, $exclude_id = null) {

		// SQL文
		$sql = '
			SELECT COUNT(*)
			FROM
				t_mail_recipients m
			WHERE
				m.email = ?
		';

		$params = [$email];

		if ($exclude_id != null) {
			$sql		.= ' AND id != ?';
			$params[]	= $exclude_id;
		}

		// 実行して一致するIDの数が1以上の場合はtrueを返す
		return $this->db->GetOne($sql, $params) > 0;
	}

	/**
	 * メールの取得
	 *
	 * @access	public
	 * @param	$id
	 * @return	array
	 */
	public function getMailById($id) {

		// SQL文
		$sql = '
		SELECT
			m.id, m.email
		FROM
			t_mail_recipients m
		WHERE
			m.id = ?
		';

		// 実行
		return $this->db->GetRow($sql, [$id]);
	}

	/**
	 * メールの更新
	 *
	 * @access	public
	 * @param	$id
	 * @return	array
	 */
	public function updateMail($edit_data) {

		// SQL文
		$sql = '
			UPDATE
				t_mail_recipients
			SET
				email = ?
			WHERE
				id = ?
		';

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// フォームデータ取得
			$params = [
				$edit_data['email'],
				$edit_data['mail_id']
			];

			// 実行
			$rs = $this->db->Execute($sql, $params);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// トランザクション成功
			$this->db->CommitTrans();
			return true;

		} catch (Exception $e) {

			// トランザクション失敗
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * メールの削除
	 *
	 * @access	public
	 * @param	$user_ids
	 * @return
	 */
	public function deleteMails($mail_ids) {

		// プレースホルダを削除対象の数作成（例：?,?,?,...）
		$placeholders = implode(',', array_fill(0, count($mail_ids), '?'));

		// SQL文
		$sql = "
			UPDATE
				t_mail_recipients
			SET
				is_active 	= 0,
				sort_order 	= 0,
				updated_at 	= NOW()
			WHERE
				id IN ($placeholders)
		";

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// 実行
			$rs = $this->db->Execute($sql, $mail_ids);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// トランザクション成功
			$this->db->CommitTrans();
			return true;

		} catch (Exception $e) {

			// トランザクション失敗
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * メールの削除IDの存在チェック
	 *
	 * @access	public
	 * @param	$mail_ids
	 * @return
	 */
	public function existsMailIds($mail_ids) {

		// 配列が空ならfalse
		if (empty($mail_ids)) return false;

		// プレースホルダを削除対象の数作成（例：?,?,?,...）
		$placeholders = implode(',', array_fill(0, count($mail_ids), '?'));

		// SQL文
		$sql = "
			SELECT
				COUNT(*) AS cnt
			FROM
				t_mail_recipients m
			WHERE
				m.id IN ($placeholders)
			AND
				m.is_active = 1
		";

		// 実行
		$rs = $this->db->GetRow($sql, $mail_ids);

		// 選択したIDと存在するIDが一致すればtrue
		return (int)$rs['cnt'] == count($mail_ids);
	}

	/**
	 * 並び替え順の更新
	 *
	 * @access	public
	 * @param	$user_ids
	 * @return
	 */
	public function updateMailSortOrder($sort_ids) {

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// 並び替え順の更新の実行
			$this->_changeMailSortOrders($sort_ids);

			// 全て成功したらコミットで反映
			$this->db->CommitTrans();

		} catch (Exception $e) {

			// 1つでも失敗したらロールバック
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * 並び替え順の更新の実行
	 *
	 * @access	public
	 * @param	$sort_ids
	 * @return
	 */
	private function _changeMailSortOrders($sort_ids) {

		foreach ($sort_ids as $index => $id) {

			// SQL文
			$sql = '
				UPDATE
					t_mail_recipients
				SET
					sort_order = ?,
					updated_at = NOW()
				WHERE
					id = ?
			';

			$params = [
				$index + 1,	// 並び順は1スタート
				$id			// 対象のメールID
			];

			// 実行
			$rs = $this->db->Execute($sql, $params);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}
		}
	}

	// ============================================================
	// 路線管理
	// ============================================================

	/**
	 * 路線のIDの重複チェック
	 *
	 * @access	public
	 * @param	$id
	 * @return	$count > 0
	 */
	public function isDuplicateRoute($route, $exclude_id = null) {

		// SQL文
		$sql = '
			SELECT COUNT(*)
			FROM
				t_routes r
			WHERE
				r.route_name = ?
		';

		$params = [$route];

		if ($exclude_id != null) {
			$sql 		.= ' AND id != ?';
			$params[]	= $exclude_id;
		}

		// 実行して一致するIDの数が1以上の場合はtrueを返す
		return $this->db->GetOne($sql, $params) > 0;
	}

	/**
	 * 路線の登録
	 *
	 * @access	public
	 * @param	$form_data
	 * @return	array
	 */
	public function insertRoute($form_data) {

		// SQL文
		$sql_sort_order = '
			SELECT
				COALESCE(MAX(r.sort_order), 0)
			FROM
				t_routes r
		';

		// SQL文
		$sql_insert = '
			INSERT INTO t_routes (
				route_name, sort_order
			) VALUES (
				?, ?
			)
		';

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// sort_orderの最大値の連番を作成
			$max		= $this->db->GetOne($sql_sort_order);
			$sort_order	= $max + 1;

			// 実行
			$rs = $this->db->Execute($sql_insert, [$form_data['route'], $sort_order]);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// トランザクション成功
			$this->db->CommitTrans();
			return true;

		} catch (Exception $e) {

			// トランザクション失敗
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * 路線の取得
	 *
	 * @access	public
	 * @param	$id
	 * @return	array
	 */
	public function getRouteById($id) {

		// SQL文
		$sql = '
			SELECT
				r.id, r.route_name
			FROM
				t_routes r
			WHERE
				r.id = ?
		';

		// 実行
		return $this->db->GetRow($sql, [$id]);
	}

	/**
	 * 路線の更新
	 *
	 * @access	public
	 * @param	$id
	 * @return	array
	 */
	public function updateRoute($edit_data) {

		// SQL文
		$sql = '
			UPDATE
				t_routes
			SET
				route_name = ?
			WHERE
				id = ?
		';

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// フォームデータ取得
			$params = [
				$edit_data['route'],
				$edit_data['route_id']
			];

			// 実行
			$rs = $this->db->Execute($sql, $params);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// トランザクション成功
			$this->db->CommitTrans();
			return true;

		} catch (Exception $e) {

			// トランザクション失敗
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * 路線の削除
	 *
	 * @access	public
	 * @param	$route_ids
	 * @return
	 */
	public function deleteRoutes($route_ids) {

		// プレースホルダを削除対象の数作成（例：?,?,?,...）
		$placeholders = implode(',', array_fill(0, count($route_ids), '?'));

		// SQL文
		$sql = "
			UPDATE
				t_routes
			SET
				is_active	= 0,
				sort_order	= 0,
				updated_at	= NOW()
			WHERE
				id IN ($placeholders)
		";

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// 実行
			$rs = $this->db->Execute($sql, $route_ids);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// トランザクション成功
			$this->db->CommitTrans();
			return true;

		} catch (Exception $e) {

			// トランザクション失敗
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * 路線の削除IDの存在チェック
	 *
	 * @access	public
	 * @param	$route_ids
	 * @return
	 */
	public function existsRouteIds($route_ids) {

		// 配列が空ならfalse
		if (empty($route_ids)) return false;

		// プレースホルダを削除対象の数作成（例：?,?,?,...）
		$placeholders = implode(',', array_fill(0, count($route_ids), '?'));

		// SQL文
		$sql = "
			SELECT
				COUNT(*) AS cnt
			FROM
				t_routes r
			WHERE
				r.id IN ($placeholders)
			AND
				r.is_active = 1
		";

		// 実行
		$rs = $this->db->GetRow($sql, $route_ids);

		// 選択したIDと存在するIDが一致すればtrue
		return (int)$rs['cnt'] == count($route_ids);
	}

	/**
	 * 並び替え順の更新
	 *
	 * @access	public
	 * @param	$user_ids
	 * @return
	 */
	public function updateRouteSortOrder($sort_ids) {

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// 並び替え順の更新の実行
			$this->_changeRouteSortOrders($sort_ids);

			// 全て成功したらコミットで反映
			$this->db->CommitTrans();

		} catch (Exception $e) {

			// 1つでも失敗したらロールバック
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * 並び替え順の更新の実行
	 *
	 * @access	public
	 * @param	$sort_ids
	 * @return
	 */
	private function _changeRouteSortOrders($sort_ids) {

		foreach ($sort_ids as $index => $id) {

			// SQL文
			$sql = '
				UPDATE
					t_routes
				SET
					sort_order = ?,
					updated_at = NOW()
				WHERE
					id = ?
			';

			$params = [
				$index + 1,	// 並び順は1スタート
				$id			// 対象のメールID
			];

			// 実行
			$rs = $this->db->Execute($sql, $params);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}
		}
	}

	// ============================================================
	// 種別管理
	// ============================================================

	/**
	 * 種別のIDの重複チェック
	 *
	 * @access	public
	 * @param	$id
	 * @return	$count > 0
	 */
	public function isDuplicateType($type, $exclude_id = null) {

		// SQL文
		$sql = '
			SELECT COUNT(*)
			FROM
				t_types t
			WHERE
				t.type_name = ?
		';

		$params = [$type];

		if ($exclude_id != null) {
			$sql 		.= ' AND id != ?';
			$params[]	= $exclude_id;
		}

		// 実行して一致するIDの数が1以上の場合はtrueを返す
		return $this->db->GetOne($sql, $params) > 0;
	}

	/**
	 * 種別の登録
	 *
	 * @access	public
	 * @param	$form_data
	 * @return	array
	 */
	public function insertType($form_data) {

		// SQL文
		$sql_sort_order = '
			SELECT
				COALESCE(MAX(t.sort_order), 0)
			FROM
				t_types t
		';

		// SQL文
		$sql_insert = '
			INSERT INTO t_types (
				type_name, sort_order
			) VALUES (
				?, ?
			)
		';

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// sort_orderの最大値の連番を作成
			$max		= $this->db->GetOne($sql_sort_order);
			$sort_order	= $max + 1;

			// 実行
			$rs = $this->db->Execute($sql_insert, [$form_data['type'], $sort_order]);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// トランザクション成功
			$this->db->CommitTrans();
			return true;

		} catch (Exception $e) {

			// トランザクション失敗
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * 種別の取得
	 *
	 * @access	public
	 * @param	$id
	 * @return	array
	 */
	public function getTypeById($id) {

		// SQL文
		$sql = '
			SELECT
				t.id, t.type_name
			FROM
				t_types t
			WHERE
				t.id = ?
		';

		// 実行
		return $this->db->GetRow($sql, [$id]);
	}

	/**
	 * 種別の更新
	 *
	 * @access	public
	 * @param	$id
	 * @return	array
	 */
	public function updateType($edit_data) {

		// SQL文
		$sql = '
			UPDATE
				t_types
			SET
				type_name = ?
			WHERE
				id = ?
		';

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// フォームデータ取得
			$params = [
				$edit_data['type'],
				$edit_data['type_id']
			];

			// 実行
			$rs = $this->db->Execute($sql, $params);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// トランザクション成功
			$this->db->CommitTrans();
			return true;

		} catch (Exception $e) {

			// トランザクション失敗
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * 種別の削除
	 *
	 * @access	public
	 * @param	$type_ids
	 * @return
	 */
	public function deleteTypes($type_ids) {

		// プレースホルダを削除対象の数作成（例：?,?,?,...）
		$placeholders = implode(',', array_fill(0, count($type_ids), '?'));

		// SQL文
		$sql = "
			UPDATE
				t_types
			SET
				is_active	= 0,
				sort_order	= 0,
				updated_at	= NOW()
			WHERE
				id IN ($placeholders)
		";

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// 実行
			$rs = $this->db->Execute($sql, $type_ids);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// トランザクション成功
			$this->db->CommitTrans();
			return true;

		} catch (Exception $e) {

			// トランザクション失敗
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * 種別の削除IDの存在チェック
	 *
	 * @access	public
	 * @param	$type_ids
	 * @return
	 */
	public function existsTypeIds($type_ids) {

		// 配列が空ならfalse
		if (empty($type_ids)) return false;

		// プレースホルダを削除対象の数作成（例：?,?,?,...）
		$placeholders = implode(',', array_fill(0, count($type_ids), '?'));

		// SQL文
		$sql = "
			SELECT
				COUNT(*) AS cnt
			FROM
				t_types t
			WHERE
				t.id IN ($placeholders)
			AND
				t.is_active = 1
		";

		// 実行
		$rs = $this->db->GetRow($sql, $type_ids);

		// 選択したIDと存在するIDが一致すればtrue
		return (int)$rs['cnt'] == count($type_ids);
	}

	/**
	 * 並び替え順の更新
	 *
	 * @access	public
	 * @param	$user_ids
	 * @return
	 */
	public function updateTypeSortOrder($sort_ids) {

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// 並び替え順の更新の実行
			$this->_changeTypeSortOrders($sort_ids);

			// 全て成功したらコミットで反映
			$this->db->CommitTrans();

		} catch (Exception $e) {

			// 1つでも失敗したらロールバック
			$this->db->RollbackTrans();
			throw $e;
		}
	}

	/**
	 * 並び替え順の更新の実行
	 *
	 * @access	public
	 * @param   $sort_ids
	 * @return
	 */
	private function _changeTypeSortOrders($sort_ids) {

		foreach ($sort_ids as $index => $id) {

			// SQL文
			$sql = '
				UPDATE
					t_types
				SET
					sort_order = ?,
					updated_at = NOW()
				WHERE
					id = ?
			';

			$params = [
				$index + 1,	// 並び順は1スタート
				$id			// 対象のメールID
			];

			// 実行
			$rs = $this->db->Execute($sql, $params);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}
		}
	}
}
