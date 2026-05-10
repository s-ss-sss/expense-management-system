<?php

require_once 'CommonDao.class.php';

class ExpenseDao extends CommonDao {

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
	 * よく使うコースの全件取得
	 *
	 * @access	public
	 * @param   $user_id
	 * @return  array
	 */
	public function getCourses($user_id) {

		// SQL文
		$sql = '
			SELECT
				c.id, c.user_id, c.course_name, c.route_id, c.type_id, c.section_from,
				c.section_to, c.fee, c.note, c.is_active, c.created_at
			FROM
				t_courses c
			WHERE
				c.user_id 	= ? AND
				c.is_active = 1
			ORDER BY
				c.created_at DESC,
				c.id DESC
		';

		// 1行ずつ配列に追加
		return $this->db->Execute($sql, [$user_id])->GetArray();
	}

	// ============================================================
	// 旅費請求
	// ============================================================

	/**
	 * 旅費請求の登録
	 *
	 * @access	public
	 * @param	$user_id, $form_data
	 * @return
	 */
	public function insertExpense($user_id, $form_data) {

		// SQL文
		$sql = '
			INSERT INTO t_expenses (
				user_id, purchase_date, route_id, type_id, section_from,
				section_to, fee, note
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?
			)
		';

		// 入力行をカウント
		$n = count($form_data['date'] ?? []);

		// 入力行がなければ例外処理
		if ($n == 0) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			throw new Exception(LOG_NO_TARGET);
		}

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// フォームデータをデータベースに登録
			for ($i = 0; $i < $n; $i++) {

				$params = [
					$user_id,
					$form_data['date'][$i],
					$form_data['route'][$i],
					$form_data['type'][$i],
					$form_data['start'][$i],
					$form_data['end'][$i],
					$form_data['fee'][$i],
					$form_data['note'][$i]
				];

				// 実行
				$rs = $this->db->Execute($sql, $params);

				// 実行が失敗したら例外処理
				if ($rs === false) {
					trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
					throw new Exception(LOG_SQL_FAILED);
				}

				// 挿入件数が1行でなければ例外処理
				if ($this->db->Affected_Rows() != 1) {
					trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
					throw new Exception(LOG_UNEXPECTED_COUNT);
				}
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
	 * 申請後のよく使うコースの登録
	 *
	 * @access	public
	 * @param	$user_id, $form_data
	 * @return
	 */
	public function insertExpenseCourse($user_id, $form_data) {

		// SQL文
		$sql = '
			INSERT INTO t_courses (
				user_id, course_name, route_id, type_id, section_from,
				section_to, fee, note
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?
			)
		';

		// 対象行を取り出して数値化と重複排除
		$targets = $form_data['course_register'] ?? [];
		$targets = array_values(array_unique(array_map('intval', $targets)));

		// 入力行がなければ例外処理
		if (count($targets) == 0) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			throw new Exception(LOG_NO_TARGET);
		}

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// フォームデータをデータベースに登録
			foreach ($form_data['course_register'] as $num) {

				$params = [
					$user_id,
					$form_data['course_name'][$num],
					$form_data['route'][$num],
					$form_data['type'][$num],
					$form_data['start'][$num],
					$form_data['end'][$num],
					$form_data['fee'][$num],
					$form_data['note'][$num]
				];

				// 実行
				$rs = $this->db->Execute($sql, $params);

				// 実行が失敗したら例外処理
				if ($rs === false) {
					trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
					throw new Exception(LOG_SQL_FAILED);
				}

				// 挿入件数が1行でなければ例外処理
				if ($this->db->Affected_Rows() != 1) {
					trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
					throw new Exception(LOG_UNEXPECTED_COUNT);
				}
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

	// ============================================================
	// 履歴確認
	// ============================================================

	/**
	 * 旅費請求データの取得
	 *
	 * @access	public
	 * @param	$user_id, $form_data
	 * @return	array
	 */
	public function getHistory($user_id, $from, $to) {

		// SQL文
		$sql = "
			SELECT
				e.user_id, e.purchase_date, e.route_id, e.type_id, e.section_from,
				e.section_to, e.fee, e.note, e.is_active, e.created_at
			FROM
				t_expenses e
			WHERE
				e.user_id 	= ? AND
				e.is_active = 1 AND
				e.created_at >= CAST(? AS DATE) AND
				e.created_at < DATE_ADD(CAST(? AS DATE), INTERVAL 1 DAY)
			ORDER BY
				DATE(e.created_at) ASC,
				e.purchase_date ASC,
				e.id ASC
		";

		$params = [$user_id, $from, $to];

		// 1行ずつ配列に追加
		return $this->db->Execute($sql, $params)->GetArray();
	}

	// ============================================================
	// よく使うコース
	// ============================================================

	/**
	 * よく使うコースの登録
	 *
	 * @access	public
	 * @param	$user_id, $form_data
	 * @return
	 */
	public function insertCourse($user_id, $form_data) {

		// SQL文
		$sql = '
			INSERT INTO t_courses (
				user_id, course_name, route_id, type_id, section_from,
				section_to, fee, note
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?
			)
		';

		// 入力行をカウント
		$n = count($form_data['course_name'] ?? []);

		// 入力行がなければ例外処理
		if ($n == 0) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			throw new Exception(LOG_NO_TARGET);
		}

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// フォームデータをデータベースに登録
			foreach ($form_data['course_name'] as $i => $course_name) {

				// 空行はスキップ
				if (empty($course_name)) {
					continue;
				}

				$params = [
					$user_id,
					$form_data['course_name'][$i],
					$form_data['route'][$i],
					$form_data['type'][$i],
					$form_data['start'][$i],
					$form_data['end'][$i],
					$form_data['fee'][$i],
					$form_data['note'][$i]
				];

				// 実行
				$rs = $this->db->Execute($sql, $params);

				// 実行が失敗したら例外処理
				if ($rs === false) {
					trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
					throw new Exception(LOG_SQL_FAILED);
				}

				// 挿入件数が1行でなければ例外処理
				if ($this->db->Affected_Rows() != 1) {
					trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
					throw new Exception(LOG_UNEXPECTED_COUNT);
				}
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
	 * よく使うコースの取得
	 *
	 * @access	public
	 * @param	$user_id, $course_id
	 * @return	array
	 */
	public function getCourseById($user_id, $course_id) {

		// SQL文
		$sql = '
			SELECT
				c.id, c.user_id, c.course_name, c.route_id, c.type_id, c.section_from,
				c.section_to, c.fee, c.note, c.is_active, c.created_at
			FROM
				t_courses c
			WHERE
				c.user_id		= ? AND
				c.id			= ? AND
				c.is_active		= 1
		';

		// 実行
		return $this->db->GetRow($sql, [$user_id, $course_id]);
	}

	/**
	 * よく使うコースの更新
	 *
	 * @access	public
	 * @param	$user_id, $course_id, $form_data
	 * @return
	 */
	public function updateCourse($user_id, $course_id, $form_data) {

		// SQL文
		$sql = '
			UPDATE
				t_courses
			SET
				course_name 	= ?,
				route_id 		= ?,
				type_id 		= ?,
				section_from 	= ?,
				section_to		= ?,
				fee				= ?,
				note			= ?,
				updated_at 		= NOW()
			WHERE
				user_id = ? AND
				id 		= ?
		';

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// フォームデータ取得
			$params = [
				$form_data['course_name'],
				$form_data['route'],
				$form_data['type'],
				$form_data['start'],
				$form_data['end'],
				$form_data['fee'],
				$form_data['note'],
				$user_id,
				$course_id
			];

			// 実行
			$rs = $this->db->Execute($sql, $params);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// 挿入件数が1行でなければ例外処理
			if ($this->db->Affected_Rows() != 1) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_UNEXPECTED_COUNT);
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
	 * よく使うコースの削除
	 *
	 * @access	public
	 * @param	$user_id, $course_ids
	 * @return
	 */
	public function deleteCourses($user_id, $course_ids) {

		// プレースホルダを削除対象の数作成（例：?,?,?,...）
		$placeholders = implode(',', array_fill(0, count($course_ids), '?'));

		// SQL文
		$sql = "
			UPDATE
				t_courses
			SET
				is_active	= 0,
				updated_at	= NOW()
			WHERE
				user_id = ? AND
				id IN ($placeholders)
		";

		// トランザクション開始
		$this->db->BeginTrans();

		try {
			// ユーザーIDとコース配列をマージ
			$params = array_merge([$user_id], $course_ids);

			// 実行
			$rs = $this->db->Execute($sql, $params);

			// 実行が失敗したら例外処理
			if ($rs === false) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_SQL_FAILED);
			}

			// 挿入件数が0行であれば例外処理
			if ($this->db->Affected_Rows() == 0) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_UNEXPECTED_COUNT);
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
	 * よく使うコースの削除IDの存在チェック
	 *
	 * @access	public
	 * @param	$course_ids
	 * @return
	 */
	public function existsCourseIds($course_ids) {

		// 配列が空ならfalse
		if (empty($course_ids)) return false;

		// プレースホルダを削除対象の数作成（例：?,?,?,...）
		$placeholders = implode(',', array_fill(0, count($course_ids), '?'));

		// SQL文
		$sql = "
			SELECT
				COUNT(*) AS cnt
			FROM
				t_courses
			WHERE
				id IN ($placeholders)
			AND
				is_active = 1
		";

		// 実行
		$rs = $this->db->GetRow($sql, $course_ids);

		// 選択したIDと存在するIDが一致すればtrue
		return (int)$rs['cnt'] == count($course_ids);
	}
}
