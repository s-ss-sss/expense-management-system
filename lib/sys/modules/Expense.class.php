<?php

require_once 'Common.class.php';

class Expense extends Common {

	// コンストラクタ
	public function __construct($dao) {
		parent::__construct($dao);
	}

	// ============================================================
	// 共通処理
	// ============================================================

	/**
	 * バリデーション：単数行
	 *
	 * @access	public
	 * @param	$form_data, $options = []
	 * @return	$errors
	 */
	public function validateRow($form_data, $options = []) {
		$errors = [];

		// 購入日がある場合はマージ
		if (isset($options['date'])) {
			$errors = array_merge($errors, $this->validateDate($form_data['date'] ?? ''));
		}

		// コース名がある場合はマージ
		if (isset($options['course_name'])) {
			$errors = array_merge($errors, $this->validateCourseName($form_data['course_name'] ?? ''));
		}

		// 路線
		if (empty($form_data['route'])) {
			$errors['route'] = '路線を選択してください';
		}

		// 種別
		if (empty($form_data['type'])) {
			$errors['type'] = '種別を選択してください';
		}

		// 区間の文字用
		if (empty($form_data['start']) || empty($form_data['end'])) {
			$errors['section'] = '区間を入力してください';
		} elseif (mb_strlen($form_data['start']) > 40 || mb_strlen($form_data['end']) > 40) {
			$errors['section'] = '区間は40文字以内で入力してください';
		}

		// 区間（開始）の赤枠用
		if (empty($form_data['start'])) {
			$errors['start'] = true;
		} elseif (mb_strlen($form_data['start']) > 40) {
			$errors['start'] = true;
		}

		// 区間（終了）の赤枠用
		if (empty($form_data['end'])) {
			$errors['end'] = true;
		} elseif (mb_strlen($form_data['end']) > 40) {
			$errors['end'] = true;
		}

		// 料金
		if (empty($form_data['fee'])) {
			$errors['fee'] = '料金を入力してください';
		} elseif (! ctype_digit($form_data['fee'])) {
			$errors['fee'] = '料金は半角数字で入力してください';
		} elseif (strlen($form_data['fee']) > 5) {
			$errors['fee'] = '料金は5桁以内で入力してください';
		}

		// 訪問先
		if (! empty($form_data['note']) && mb_strlen($form_data['note']) > 100) {
			$errors['note'] = '訪問先は100文字以内で入力してください';
		}

		return $errors;
	}

	/**
	 * バリデーション：複数行
	 *
	 * @access	public
	 * @param	$form_data, $options = []
	 * @return	array_filter($errors)
	 */
	public function validateRows($form_data, $options = []) {
		$errors = [];

		// 行数を取得
		$row_count = max($this->getRowCount($form_data ?? []), 1);

		for ($i = 0; $i < $row_count; $i++) {
			$row = [
				'date'			=> $form_data['date'][$i] ?? null,
				'course_name'	=> $form_data['course_name'][$i] ?? null,
				'route'			=> $form_data['route'][$i] ?? null,
				'type'			=> $form_data['type'][$i] ?? null,
				'start'			=> $form_data['start'][$i] ?? null,
				'end'			=> $form_data['end'][$i] ?? null,
				'fee'			=> $form_data['fee'][$i] ?? null,
				'note'			=> $form_data['note'][$i] ?? null,
			];
			$errors[$i] = $this->validateRow($row, $options);
		}

		// 空のエラーは削除
		return array_filter($errors);
	}

	/**
	 * バリデーション：購入日
	 *
	 * @access	public
	 * @param   $date
	 * @return  $errors
	 */
	public function validateDate($date) {
		$errors = [];

		if (empty($date)) {
			$errors['date'] = '購入日を選択してください';
		} elseif (!preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $date)) {
			$errors['date'] = 'yyyy/mm/ddの形式で入力してください';
		} else {
			list($y, $m, $d) = explode('/', $date);
			if (!checkdate((int)$m, (int)$d, (int)$y)) {
				$errors['date'] = '存在しない日付が入力されています';
			}
		}

		return $errors;
	}

	/**
	 * バリデーション：コース名
	 *
	 * @access	public
	 * @param   $course_name
	 * @return  $errors
	 */
	public function validateCourseName($course_name) {
		$errors = [];

		if (empty($course_name)) {
			$errors['course_name'] = 'コース名を入力してください';
		} elseif (mb_strlen($course_name) > 40) {
			$errors['course_name'] = 'コース名は40文字以内で入力してください';
		}

		return $errors;
	}

	/**
	 * フォームデータの入力項目から行数をカウント
	 *
	 * @access	public
	 * @param   $form_data
	 * @return  $max
	 */
	public function getRowCount($form_data) {
		$max = 0;

		// 入力項目の最大数を更新
		foreach ($form_data as $field_values) {
			if (is_array($field_values)) {
				$max = max($max, count($field_values));
			}
		}

		return $max;
	}

	// ============================================================
	// 旅費請求
	// ============================================================

	/**
	 * 入力画面の表示
	 *
	 * @access	public
	 * @param	$form_data = null, $errors = []
	 * @return
	 */
	public function displayExpenseForm($form_data = null, $errors = []) {
		global $smarty;

		// リダイレクトフラグがあればよく使うコース画面に遷移
		if ($this->_redirectExpenseCourse()) {
			return;
		}

		// リダイレクトフラグがあれば完了画面に遷移
		if ($this->_redirectExpenseComplete()) {
			return;
		}

		// フォームデータ取得
		if (empty($form_data)) {
			$form_data = $_SESSION['form_data'] ?? [
				'date'				=> [''],
				'route'				=> [''],
				'type'				=> [''],
				'start'				=> [''],
				'end'				=> [''],
				'fee'				=> [''],
				'note'				=> [''],
				'course_name'		=> [''],
				'course_register'	=> ['']
			];

			// セッションクリア
			unset($_SESSION['form_data']);
		}

		// 行数を取得
		$row_count = max($this->getRowCount($form_data ?? []), 1);

		// セッションのユーザーID取得
		$user_id = $_SESSION['expense']['UserID'] ?? null;

		// よく使うコースの取得
		$course_lists = $this->dao->getCourses($user_id);

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'routes'		=> $this->getLabels()['routes'],
			'types'			=> $this->getLabels()['types'],
			'form_data'		=> $form_data,
			'errors'		=> $errors,
			'row_count'		=> $row_count,
			'course_lists'	=> $course_lists,
			'is_admin'		=> $_SESSION['expense']['is_admin'],
			'section'		=> 'expense'
		]);

		$smarty->display('user/expense/form.tpl');
	}

	/**
	 * 確認画面の表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayExpenseConfirm() {
		global $smarty;

		// フォームデータ取得
		$form_data = [
			'date'				=> array_map('trimFull', $_POST['date'] ?? ['']),
			'route'				=> array_map('trimFull', $_POST['route'] ?? ['']),
			'type'				=> array_map('trimFull', $_POST['type'] ?? ['']),
			'start'				=> array_map('trimFull', $_POST['start'] ?? ['']),
			'end'				=> array_map('trimFull', $_POST['end'] ?? ['']),
			'fee'				=> array_map('trimFull', $_POST['fee'] ?? ['']),
			'note'				=> array_map('trimFull', $_POST['note'] ?? ['']),
			'course_name'		=> array_map('trimFull', $_POST['course_name'] ?? ['']),
			'course_register'	=> array_map('trimFull', $_POST['course_register'] ?? [''])
		];

		// バリデーション実行
		$errors = $this->validateRows($form_data, ['date' => true]);

		// エラー内容を再描画
		if (! empty($errors)) {
			return $this->displayExpenseForm($form_data, $errors);
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// セッションにフォームデータ保存
			$_SESSION['form_data'] = $form_data;

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'routes'		=> $this->getLabels()['routes'],
				'types'			=> $this->getLabels()['types'],
				'form_data'		=> $form_data,
				'is_admin'		=> $_SESSION['expense']['is_admin'],
				'section'		=> 'expense'
			]);

			$smarty->display('user/expense/confirm.tpl');

		} catch (Exception $e) {
			return $this->displayExpenseForm();
		}
	}

	/**
	 * 申請後のよく使うコース画面の表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayExpenseCourse() {

		// 旅費請求データの登録
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->_registExpense();

		// POST以外はトップページにリダイレクト
		} else {
			header('Location: /expense/');
			exit;
		}
	}

	/**
	 * リダイレクトフラグがあればよく使うコース画面に遷移
	 *
	 * @access	private
	 * @param
	 * @return
	 */
	private function _redirectExpenseCourse() {
		global $smarty;

		if (empty($_SESSION['redirect_course'])) {
			return false;
		}

		// セッションクリア
		unset($_SESSION['redirect_course']);

		// セッションのフォームデータ取得
		$form_data = $_SESSION['registered_data'] ?? [];

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'routes'		=> $this->getLabels()['routes'],
			'types'			=> $this->getLabels()['types'],
			'form_data'		=> $form_data,
			'is_admin'		=> $_SESSION['expense']['is_admin'],
			'section'		=> 'expense'
		]);

		try {
			// メール送信
			$this->_sendExpenseMail($form_data);

			// セッションクリア
			unset($_SESSION['registered_data']);

			$smarty->display('user/expense/course.tpl');

			// 戻り値をtrueで終了して二重実行を回避
			return true;

		} catch (Exception $e) {

			$smarty->display('user/expense/confirm.tpl');

			// 戻り値をtrueで終了して二重実行を回避
			return true;
		}
	}

	/**
	 * 旅費請求データの登録
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	private function _registExpense() {
		global $smarty;

		// セッションのユーザーID取得
		$user_id = $_SESSION['expense']['UserID'] ?? null;

		// セッションのフォームデータ取得
		$form_data = $_SESSION['form_data'] ?? [];

		// セッションクリア
		unset($_SESSION['form_data']);

		// フォームデータがない場合はトップページにリダイレクト
		if (empty($form_data)) {
			header('Location: /expense/');
			exit;
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// 旅費請求の登録
			$this->dao->insertExpense($user_id, $form_data);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_course'] = true;

			// セッションにフォームデータ保存
			$_SESSION['registered_data'] = $form_data;

			// 申請後のよく使うコース画面にリダイレクト
			header('Location: /expense/');
			exit;

		} catch (Exception $e) {

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'routes'		=> $this->getLabels()['routes'],
				'types'			=> $this->getLabels()['types'],
				'form_data'		=> $form_data,
				'is_admin'		=> $_SESSION['expense']['is_admin'],
				'section'		=> 'expense'
			]);

			return $smarty->display('user/expense/confirm.tpl');
		}
	}

	/**
	 * よく使うコース登録後の完了画面の表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayExpenseComplete() {

		// 申請後のよく使うコースの登録
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->_registExpenseCourse();

		// POST以外はトップページにリダイレクト
		} else {
			header('Location: /expense/');
			exit;
		}
	}

	/**
	 * リダイレクトフラグがあれば完了画面に遷移
	 *
	 * @access	private
	 * @param
	 * @return
	 */
	private function _redirectExpenseComplete() {
		global $smarty;

		if (empty($_SESSION['redirect_complete'])) {
			return false;
		}

		// セッションクリア
		unset($_SESSION['redirect_complete']);

		$smarty->assign([
			'layout'	=> 'user/layout.tpl',
			'url'		=> '/expense/',
			'is_admin'	=> $_SESSION['expense']['is_admin'],
			'section'	=> 'expense'
		]);

		$smarty->display('common/complete.tpl');
		return true;
	}

	/**
	 * 申請後のよく使うコースデータの登録
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	private function _registExpenseCourse() {
		global $smarty;

		// フォームデータ取得
		$form_data = [
			'date'				=> array_map('trimFull', $_POST['date'] ?? []),
			'route'				=> array_map('trimFull', $_POST['route'] ?? []),
			'type'				=> array_map('trimFull', $_POST['type'] ?? []),
			'start'				=> array_map('trimFull', $_POST['start'] ?? []),
			'end'				=> array_map('trimFull', $_POST['end'] ?? []),
			'fee'				=> array_map('trimFull', $_POST['fee'] ?? []),
			'note'				=> array_map('trimFull', $_POST['note'] ?? []),
			'course_name'		=> array_map('trimFull', $_POST['course_name'] ?? []),
			'course_register'	=> array_map('trimFull', $_POST['course_register'] ?? [])
		];

		// バリデーション実行
		$errors = $this->validateExpenseCourse($form_data);

		// 未入力で登録ボタンを押した場合
		if (empty($errors) && empty($form_data['course_register'])) {
			$errors['common'] = '登録する場合は1つ以上コースを選択してコース名を入力してください';
		}

		// バリデーションに引っかかった場合
		if (! empty($errors)) {

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'routes'		=> $this->getLabels()['routes'],
				'types'			=> $this->getLabels()['types'],
				'form_data'		=> $form_data,
				'errors'		=> $errors,
				'is_admin'		=> $_SESSION['expense']['is_admin'],
				'section'		=> 'expense'
			]);

			return $smarty->display('user/expense/course.tpl');
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// セッションのユーザーID取得
			$user_id = $_SESSION['expense']['UserID'] ?? null;

			// 申請後のよく使うコースの登録
			$this->dao->insertExpenseCourse($user_id, $form_data);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_complete'] = true;

			// 完了画面にリダイレクト
			header('Location: /expense/');
			exit;

		} catch (Exception $e) {

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'routes'		=> $this->getLabels()['routes'],
				'types'			=> $this->getLabels()['types'],
				'form_data'		=> $form_data,
				'is_admin'		=> $_SESSION['expense']['is_admin'],
				'section'		=> 'expense'
			]);

			return $smarty->display('user/expense/course.tpl');
		}
	}

	/**
	 * バリデーション：申請後のよく使うコース
	 *
	 * @access	public
	 * @param	$form_data
	 * @return	$errors
	 */
	public function validateExpenseCourse($form_data) {
		$errors = [];

		foreach ($form_data['course_name'] as $num => $name) {

			// コース名から空白除外
			$trim_name = trimFull($name);

			// コース名の真偽値
			$has_name = ($trim_name !== '');

			// フォーム番号のチェック確認
			$is_checked	= in_array((string)$num, $form_data['course_register'], true);

			// コース名ありでチェックなし
			if ($has_name && ! $is_checked) {
				$errors[$num]['checkbox'] = 'No.をチェックしてください';
			} elseif ($has_name && mb_strlen($trim_name) > 40) {
				$errors[$num]['course_name'] = 'コース名は40文字以内で入力してください';
			}

			// コース名なしでチェックあり
			if (! $has_name && $is_checked) {
				$errors[$num]['course_name'] = 'コース名を入力してください';
			}
		}

		return $errors;
	}

	/**
	 * メール送信
	 *
	 * @access	private
	 * @param	$form_data
	 * @return
	 */
	private function _sendExpenseMail($form_data) {
		global $smarty;

		// デモユーザーは送信せず処理を抜ける
		if (! empty($_SESSION['expense']['is_demo'])) {
			return true;
		}

		// ユーザーデータ取得
		$user_id	= $_SESSION['expense']['UserID'] ?? null;
		$user		= $this->dao->getUserById($user_id);
		$user_email	= $user['email'] ?? '';
		$user_name	= $user['name'] ?? '';

		// メール宛先の取得
		$mail_list			= $this->dao->getMails();
		$recipient_emails	= array_column($mail_list, 'email');

		$smarty->assign([
			'routes'	=> $this->getLabels()['routes'],
			'types'		=> $this->getLabels()['types'],
			'form_data'	=> $form_data
		]);

		// 本文
		$body = $smarty->fetch('user/mail.tpl');

		// 文字コード設定
		mb_language('Japanese');
		mb_internal_encoding('UTF-8');

		// 差出人名のエンコード
		$encoded_from = mb_encode_mimeheader(SITE_NAME, 'ISO-2022-JP', 'B');

		// 件名のエンコード
		$subject = $user_name . 'さんが旅費請求を申請しました';

		// 本文のエンコード
		$encoded_body = mb_convert_encoding($body, 'ISO-2022-JP', 'UTF-8');

		// ヘッダー
		$headers	= [];
		$headers[]	= "From: {$encoded_from} <" . MAIL_FROM . '>';
		$headers[]	= "Reply-To: {$user_email}";
		$headers[]	= 'Content-Type: text/plain; charset=ISO-2022-JP';

		// ヘッダー文字列
		$header = implode("\r\n", $headers);

		// ユーザーに個別送信
		if (! mb_send_mail($user_email, $subject, $encoded_body, $header, '-f '.MAIL_FROM)) {
			trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
			throw new Exception(LOG_MAIL_FAILED);
		}

		// 管理者に個別送信
		foreach ($recipient_emails as $admin_email) {

			// メール送信に失敗したら例外処理
			if (! mb_send_mail($admin_email, $subject, $encoded_body, $header, '-f '.MAIL_FROM)) {
				trigger_error(ERROR_USER_WARNING, E_USER_WARNING);
				throw new Exception(LOG_MAIL_FAILED);
			}
		}
	}

	// ============================================================
	// 履歴確認
	// ============================================================

	/**
	 * 履歴画面の表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayHistory() {
		global $smarty;

		// 期間選択の取得
		$start	= trimFull($_POST['start_month'] ?? '');	// 開始月
		$end	= trimFull($_POST['end_month'] ?? '');		// 終了月

		// 現在の年月を取得
		$today = new DateTime();

		// 期間処理
		$period_data = $this->_getPeriodRange($start, $end, $today);

		// 開始月の選択オプション生成
		$start_month_options = $this->_buildStartMonthOptions($today);

		// 終了月の選択オプション生成
		$end_month_options = $this->_buildEndMonthOptions($today, $period_data['start_month']);

		// 全期間の選択オプション生成
		$js_month_options = $this->_buildMonthAllOptions($today);

		// Smartyに渡す期間データの配列
		$period_info = [
			'start_month'	=> $period_data['start_month'] ?? '',
			'end_month'		=> $period_data['end_month'] ?? ''
		];

		// 検索時はバリデーション実行
		$errors = [];
		if (! empty($start) || ! empty($end)) {
			$errors = $this->validateHistory($start, $end);
		}

		// バリデーションに引っかかった場合
		if (! empty($errors)) {

			$smarty->assign([
				'errors'				=> $errors,
				'routes'				=> $this->getLabels()['routes'],
				'types'					=> $this->getLabels()['types'],
				'start_month_options'	=> $start_month_options,
				'end_month_options'		=> $end_month_options,
				'js_month_options'		=> $js_month_options,
				'period_data'			=> $period_info,
				'is_admin'				=> $_SESSION['expense']['is_admin'],
				'section'				=> 'history'
			]);

			return $smarty->display('user/history/index.tpl');
	 	}

		// セッションのユーザーID取得
		$user_id = $_SESSION['expense']['UserID'] ?? null;

		// 旅費請求データの取得
		$raw_data = $this->dao->getHistory($user_id, $period_data['from'], $period_data['to']);

		// 締め日に合わせた表示期間
		$period = [
			'from'	=> $this->_formatDateLabel($period_data['from']),
			'to'	=> $this->_formatDateLabel($period_data['to'])
		];

		// 履歴データの整形と集計
		$history = $this->_formatHistory($raw_data);

		// Smartyに渡す履歴データの配列
		$history_summary = [
			'history_data'	=> $history['history_data'] ?? [],
			'total_fee'		=> $history['total_fee'] ?? 0,
			'total_count'	=> $history['total_count'] ?? 0
		];

		$smarty->assign([
			'routes'				=> $this->getLabels()['routes'],
			'types'					=> $this->getLabels()['types'],
			'start_month_options'	=> $start_month_options,
			'end_month_options'		=> $end_month_options,
			'js_month_options'		=> $js_month_options,
			'period_data'			=> $period_info,
			'period'				=> $period,
			'history'				=> $history_summary,
			'is_admin'				=> $_SESSION['expense']['is_admin'],
			'section'				=> 'history'
		]);

		$smarty->display('user/history/index.tpl');
	}

	/**
	 * 日付を参照して曜日出力
	 *
	 * @access	private
	 * @param	$date
	 * @return	object
	 */
	private function _formatDateLabel($date) {
		$weekdays = ['日', '月', '火', '水', '木', '金', '土'];

		// 文字列の取得はDateTimeに変換
		if (! $date instanceof DateTime) {
			$date = new DateTime($date);
		}

		$weekday = $weekdays[(int)$date->format('w')];
		return $date->format('Y/n/j') . '(' . $weekday . ')';
	}

	/**
	 * 開始月の選択オプション生成
	 *
	 * @access	private
	 * @param	$today
	 * @return	$this->_buildOptionsCap($cap)
	 */
	private function _buildStartMonthOptions($today) {

		// 選択オプションの上限を計算
		$cap = $this->_calcCapMonth($today);

		// 12ヶ月の選択オプション生成
		return $this->_buildOptionsFromCap($cap);
	}

	/**
	 * 終了月の選択オプション生成
	 *
	 * @access	private
	 * @param	$today, $start
	 * @return	$options
	 */
	private function _buildEndMonthOptions($today, $start) {

		// 選択オプションの上限を計算
		$cap = $this->_calcCapMonth($today, 1);

		// 12ヶ月の選択オプション生成して並びを古い順に変更
		$options = $this->_buildOptionsFromCap($cap);

		// 開始選択より後のみ残す
		$options = array_values(array_filter($options, fn($m) => $m['value'] > $start));

		return $options;
	}

	/**
	 * 全期間の選択オプション生成
	 *
	 * @access	private
	 * @param	$today
	 * @return	$this->_buildOptionsCap($cap);
	 */
	private function _buildMonthAllOptions($today) {

		// 選択オプションの上限を計算
		$cap = $this->_calcCapMonth($today, 1);

		// 12ヶ月の選択オプション生成
		return $this->_buildOptionsFromCap($cap);
	}

	/**
	 * 12ヶ月の選択オプション生成
	 *
	 * @access	private
	 * @param	$cap
	 * @return	array_reverse($options)
	 */
	private function _buildOptionsFromCap($cap) {

		// 期間選択のオプションタグ
		$options = [];

		// 今月までの12ヶ月分を生成
		for ($i = 0; $i < 12; $i++) {

			// 現在の月を取得
			$target = (clone $cap)->modify('-' . $i . ' month');

			$options[] = [
				'value' => $target->format('Y-m'),	// value設定値
				'label' => $target->format('Y/n')	// 表示設定値
			];
		}

		// 選択肢の並びを古い順に変更
		return array_reverse($options);
	}

	/**
	 * 選択オプションの上限を計算
	 *
	 * @access	private
	 * @param	$today, $plus_months = 0
	 * @return	$cap
	 */
	private function _calcCapMonth($today, $plus_months = 0) {

		// 当月を取得
		$cap = (clone $today)->modify('first day of this month');

		// 21日以前は当月-1を上限に設定
		if ((int)$today->format('d') < 21) {
			$cap->modify('-1 month');
		}

		// $plus_monthsが0でなければ+1を上限に設定
		if ($plus_months != 0) {
			$cap->modify('+1 month');
		}

		return $cap;
	}

	/**
	 * 履歴データの整形と集計
	 *
	 * @access	private
	 * @param	$raw_data
	 * @return	array
	 */
	private function _formatHistory($raw_data) {

		// 旅費請求データの表示形式調整
		$history_data = [];

		// 合計金額の初期値
		$total_fee = 0;

		foreach ($raw_data as $row) {
			$row['created_at']		= $this->_formatDateLabel($row['created_at']);
			$row['purchase_date']	= $this->_formatDateLabel($row['purchase_date']);

			// 合計金額を加算
			$total_fee += (int)$row['fee'];

			// 配列に表示形式調整データ追加
			$history_data[] = $row;
		}

		return [
			'history_data'	=> $history_data,
			'total_fee'		=> $total_fee,
			'total_count'	=> count($history_data)
		];
	}

	/**
	 * 期間処理
	 *
	 * @access	private
	 * @param	$start = null, $end = null, $today = null
	 * @return	array
	 */
	private function _getPeriodRange($start = null, $end = null, $today = null) {

		// 初期表示
		if (empty($start) || empty($end)) {

			// 現在の年月の出力形式変更
			$format_today = $today->format('Y-m');

			// 前月を算出
			$last_month = (new DateTime('first day of last month'))->format('Y-m');

			// 翌月を算出
			$next_month = (new DateTime('first day of next month'))->format('Y-m');

			// 21日以前 → 前月21日〜今月20日
			if ($today < new DateTime($format_today . '-21')) {
				return [
					'from'			=> $last_month . '-21',		// 期間選択：締め日に調整した開始月日
					'to'			=> $format_today . '-20',	// 期間選択：締め日に調整した終了月日
					'start_month'	=> $last_month,				// selected：開始月
					'end_month'		=> $format_today			// selected：終了月
				];

			// 21日以降 → 今月21日〜翌月20日
			} else {
				return [
					'from'			=> $format_today . '-21',
					'to'			=> $next_month . '-20',
					'start_month'	=> $format_today,
					'end_month'		=> $next_month
				];
			}
		}

		// 絞り込み検索
		return [
			'from'			=> $start . '-21',
			'to'			=> $end . '-20',
			'start_month'	=> $start,
			'end_month'		=> $end
		];
	}

	/**
	 * バリデーション：絞り込み検索
	 *
	 * @access	public
	 * @param	$start, $end
	 * @return	$error
	 */
	public function validateHistory($start, $end) {
		$errors = [];

		// 形式チェック
		$pattern = '/^\d{4}-(0[1-9]|1[0-2])$/';

		// 期間
		if (! preg_match($pattern, $start) || ! preg_match($pattern, $end)) {
			$errors['period'] = '期間の形式が正しくありません';
		} elseif (empty($start) || empty($end)) {
			$errors['period'] = '期間を選択してください';
		} elseif ($start == $end) {
			$errors['period'] = '21日締めのため同じ月は選択できません';
		} elseif ($start > $end) {
			$errors['period'] = '開始月は終了月より前を選択してください';
		}

		return $errors;
	}

	// ============================================================
	// よく使うコース
	// ============================================================

	/**
	 * 一覧：表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayCourseIndex() {
		global $smarty;

		// セッションのユーザーID取得
		$user_id = $_SESSION['expense']['UserID'] ?? null;

		// よく使うコースの取得
		$course_lists = $this->dao->getCourses($user_id);

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'routes'		=> $this->getLabels()['routes'],
			'types'			=> $this->getLabels()['types'],
			'course_lists'	=> $course_lists,
			'is_admin'		=> $_SESSION['expense']['is_admin'],
			'section'		=> 'course'
		]);

		// 完了のリダイレクトフラグがあれば完了画面に遷移
		if (! empty($_SESSION['redirect_complete'])) {

			// セッションクリア
			unset($_SESSION['redirect_complete']);

			$smarty->assign([
				'layout'	=> 'user/layout.tpl',
				'url'		=> '/expense/course/'
			]);

			return $smarty->display('common/complete.tpl');
		}

		// 削除のリダイレクトフラグがあればトップページに遷移
		if (! empty($_SESSION['redirect_deleted'])) {

			// 削除アラートフラグ
			$smarty->assign('redirect_deleted', true);

			// セッションクリア
			unset($_SESSION['redirect_deleted']);
		}

		$smarty->display('user/course/index.tpl');
	}

	/**
	 * 登録：表示
	 *
	 * @access	public
	 * @param	$form_data = null, $errors = []
	 * @return
	 */
	public function displayCourseEntryForm($form_data = null, $errors = []) {
		global $smarty;

		// フォームデータ取得
		if (empty($form_data)) {
			$form_data = $_SESSION['form_data'] ?? [
				'course_name'	=> [''],
				'route'			=> [''],
				'type'			=> [''],
				'start'			=> [''],
				'end'			=> [''],
				'fee'			=> [''],
				'note'			=> ['']
			];

			// セッションクリア
			unset($_SESSION['form_data']);
		}

		// 行数を取得
		$row_count = max($this->getRowCount($form_data ?? []), 1);

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'routes'		=> $this->getLabels()['routes'],
			'types'			=> $this->getLabels()['types'],
			'form_data'		=> $form_data,
			'errors'		=> $errors,
			'row_count'		=> $row_count,
			'is_admin'		=> $_SESSION['expense']['is_admin'],
			'section'		=> 'course'
		]);

		$smarty->display('user/course/entry_form.tpl');
	}

	/**
	 * 登録：確認
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayCourseEntryConfirm() {
		global $smarty;

		// フォームデータ取得
		$form_data = [
			'course_name'	=> array_map('trimFull', $_POST['course_name'] ?? ['']),
			'route'			=> array_map('trimFull', $_POST['route'] ?? ['']),
			'type'			=> array_map('trimFull', $_POST['type'] ?? ['']),
			'start'			=> array_map('trimFull', $_POST['start'] ?? ['']),
			'end'			=> array_map('trimFull', $_POST['end'] ?? ['']),
			'fee'			=> array_map('trimFull', $_POST['fee'] ?? ['']),
			'note'			=> array_map('trimFull', $_POST['note'] ?? [''])
		];

		// バリデーション実行
		$errors = $this->validateRows($form_data, ['course_name' => true]);

		// エラー内容を再描画
		if (! empty($errors)) {
			return $this->displayCourseEntryForm($form_data, $errors);
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// セッションにフォームデータ保存
			$_SESSION['form_data'] = $form_data;

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'routes'		=> $this->getLabels()['routes'],
				'types'			=> $this->getLabels()['types'],
				'form_data'		=> $form_data,
				'is_admin'		=> $_SESSION['expense']['is_admin'],
				'section'		=> 'course'
			]);

			$smarty->display('user/course/entry_confirm.tpl');

		} catch (Exception $e) {
			return $this->displayCourseEntryForm($form_data);
		}
	}

	/**
	 * 登録：完了
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayCourseEntryComplete() {

		// よく使うコース登録
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->_registCourse();

		// POST以外はトップページにリダイレクト
		} else {
			header('Location: /expense/course/');
			exit;
		}
	}

	/**
	 * よく使うコース登録
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	private function _registCourse() {
		global $smarty;

		// セッションのユーザーID取得
		$user_id = $_SESSION['expense']['UserID'] ?? null;

		// セッションのフォームデータ取得
		$form_data = $_SESSION['form_data'] ?? [];

		// セッションクリア
		unset($_SESSION['form_data']);

		// フォームデータがない場合はトップページにリダイレクト
		if (empty($form_data)) {
			header('Location: /expense/course/');
			exit;
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// よく使うコース登録
			$this->dao->insertCourse($user_id, $form_data);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_complete'] = true;

			// 完了画面にリダイレクト
			header('Location: /expense/course/');
			exit;

		} catch (Exception $e) {

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'routes'		=> $this->getLabels()['routes'],
				'types'			=> $this->getLabels()['types'],
				'form_data'		=> $form_data,
				'is_admin'		=> $_SESSION['expense']['is_admin'],
				'section'		=> 'course'
			]);

			return $smarty->display('user/course/entry_confirm.tpl');
		}
	}

	/**
	 * 修正：表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayCourseEditForm($course_id, $form_data = null, $errors = []) {
		global $smarty;

		// コースIDが不正の場合はよく使うコースのトップにリダイレクト
		if (! ctype_digit($course_id)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayCourseIndex();
		}

		// セッションのユーザーID取得
		$user_id = $_SESSION['expense']['UserID'] ?? null;

		// よく使うコースの取得
		$course = $this->dao->getCourseById($user_id, $course_id);

		// コースが見つからない場合はよく使うコースのトップにリダイレクト
		if (empty($course)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayCourseIndex();
		}

		// フォームデータ取得
		if (empty($form_data)) {
			$form_data = $_SESSION['form_data'] ?? [
				'id'			=> $course['id'],
				'course_name'	=> $course['course_name'],
				'route'			=> $course['route_id'],
				'type'			=> $course['type_id'],
				'start'			=> $course['section_from'],
				'end'			=> $course['section_to'],
				'fee'			=> $course['fee'],
				'note'			=> $course['note']
			];

			// セッションクリア
			unset($_SESSION['form_data'], $_SESSION['edit_course']);
		}

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'routes'		=> $this->getLabels()['routes'],
			'types'			=> $this->getLabels()['types'],
			'form_data'		=> $form_data,
			'errors'		=> $errors,
			'is_admin'		=> $_SESSION['expense']['is_admin'],
			'section'		=> 'course'
		]);

		$smarty->display('user/course/edit_form.tpl');
	}

	/**
	 * 修正：確認
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayCourseEditConfirm() {
		global $smarty;

		// フォームデータ取得
		$form_data = [
			'id'			=> trimFull($_POST['course_id'] ?? ''),
			'course_name'	=> trimFull($_POST['course_name'] ?? ''),
			'route'			=> trimFull($_POST['route'] ?? ''),
			'type'			=> trimFull($_POST['type'] ?? ''),
			'start'			=> trimFull($_POST['start'] ?? ''),
			'end'			=> trimFull($_POST['end'] ?? ''),
			'fee'			=> trimFull($_POST['fee'] ?? ''),
			'note'			=> trimFull($_POST['note'] ?? '')
		];

		// コースID取得
		$course_id = trimFull($_POST['course_id'] ?? '');

		// コースIDが不正の場合はよく使うコースのトップにリダイレクト
		if (! ctype_digit($course_id)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayCourseIndex();
		}

		// バリデーション実行
		$errors = $this->validateRow($form_data, ['course_name' => true]);

		// エラー内容を再描画
		if (! empty($errors)) {
			return $this->displayCourseEditForm($course_id, $form_data, $errors);
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// セッションにフォームデータ保存
			$_SESSION['form_data']		= $form_data;
			$_SESSION['edit_course']	= [
				'course_id' => $course_id,
				'form_data' => $form_data
			];

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'routes'		=> $this->getLabels()['routes'],
				'types'			=> $this->getLabels()['types'],
				'form_data'		=> $form_data,
				'is_admin'		=> $_SESSION['expense']['is_admin'],
				'section'		=> 'course'
			]);

			$smarty->display('user/course/edit_confirm.tpl');

		} catch (Exception $e) {
			return $this->displayCourseEditForm($course_id);
		}
	}

	/**
	 * 修正：完了
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayCourseEditComplete() {

		// よく使うコース登録
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->_updateCourse();

		// POST以外はトップページにリダイレクト
		} else {
			header('Location: /expense/course/');
			exit;
		}
	}

	/**
	 * 修正：更新
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	private function _updateCourse() {
		global $smarty;

		// セッションデータ取得
		$edit_data = $_SESSION['edit_course'] ?? null;

		// セッションクリア
		unset($_SESSION['form_data'], $_SESSION['edit_course']);

		// データが不正の場合はよく使うコースのトップにリダイレクト
		if (empty($edit_data['course_id']) || empty($edit_data['form_data'])) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayCourseIndex();
		}

		// 所有者確認
		$user_id	= $_SESSION['expense']['UserID'] ?? null;
		$course_id	= $edit_data['course_id'];
		$form_data	= $edit_data['form_data'];

		// よく使うコースの取得
		$course = $this->dao->getCourseById($user_id, $course_id);

		// コースが見つからない場合はよく使うコースのトップにリダイレクト
		if (empty($course)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayCourseIndex();
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// よく使うコースの更新
			$this->dao->updateCourse($user_id, $course_id, $form_data);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_complete'] = true;

			// 完了画面にリダイレクト
			header('Location: /expense/course/');
			exit;

		} catch (Exception $e) {

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'routes'		=> $this->getLabels()['routes'],
				'types'			=> $this->getLabels()['types'],
				'form_data'		=> $form_data,
				'is_admin'		=> $_SESSION['expense']['is_admin'],
				'section'		=> 'course'
			]);

			return $smarty->display('user/course/edit_confirm.tpl');
		}
	}

	/**
	 * 削除
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function deleteCourse() {

		// 削除対象のコースID取得
		$course_ids = array_map('trimFull', $_POST['course_register'] ?? ['']);

		// データが不正な場合はトップページにリダイレクト
		if (empty($course_ids) || ! is_array($course_ids)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayCourseIndex();
		}

		// IDが不正な場合はトップページにリダイレクト
		if (! $this->dao->existsCourseIds($course_ids)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayCourseIndex();
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// セッションのユーザーID取得
			$user_id = $_SESSION['expense']['UserID'] ?? null;

			// よく使うコースの削除
			$this->dao->deleteCourses($user_id, $course_ids);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_deleted'] = true;

			// トップページにリダイレクト
			header('Location: /expense/course/');
			exit;

		} catch (Exception $e) {
			return $this->displayCourseIndex();
		}
	}
}
