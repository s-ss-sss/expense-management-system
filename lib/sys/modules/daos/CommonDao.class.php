<?php

class CommonDao {

	protected $db;

	// コンストラクタ：DBアクセス
	public function __construct($db) {
		$this->db = $db;
	}

	/**
	 * ユーザーの取得
	 *
	 * @access	public
	 * @param	$id
	 * @return	array
	 */
	public function getUserById($id) {

		// SQL文
		$sql = '
			SELECT
				u.id, u.name, u.email, u.is_admin
			FROM
				t_users u
			WHERE
				u.id = ?
		';

		// 実行
		$user = $this->db->GetRow($sql, [$id]);

		// ユーザーが取得できない場合はfullを返す
		if ($user == false) {
			return null;
		}

		return $user;
	}

	/**
	 * メールの全件取得
	 *
	 * @access	public
	 * @param
	 * @return	array
	 */
	public function getMails() {

		// SQL文
		$sql = '
			SELECT
				m.id, m.email, m.sort_order, m.is_active
			FROM
				t_mail_recipients m
			WHERE
				m.is_active = true
			ORDER BY
				m.sort_order ASC
		';

		// 1行ずつ配列に追加
		return $this->db->Execute($sql)->GetArray();
	}

	/**
	 * ログイン時のユーザーデータの取得
	 *
	 * @access	public
	 * @param	$email
	 * @return	array
	 */
	public function getUserByEmail($email) {

		// SQL文
		$sql = "
			SELECT
				u.id, u.name, u.email, u.password, u.is_admin
			FROM
				t_users u
			WHERE
				u.email		= ? AND
				u.is_active	= 1
		";

		// 実行
		$user = $this->db->GetRow($sql, [$email]);

		// ユーザーが取得できない場合はfullを返す
		if ($user == false) {
			return null;
		}

		return $user;
	}
}
