<?php

require_once 'Common.class.php';

class Admin extends Common {

	// コンストラクタ
	public function __construct($dao) {
		parent::__construct($dao);

		// 管理者チェック
		$this->_checkAdmin();
	}

	// ============================================================
	// 共通処理
	// ============================================================

	/**
	 * 管理者フラグのリダイレクト処理
	 *
	 * @access	private
	 * @param
	 * @return
	 */
	private function _checkAdmin() {
		if ($_SESSION['expense']['is_admin'] != '1') {
			header('Location: ' . BASE_URL);
			exit;
		}
	}

	/**
	 * 権限フラグのラベル
	 *
	 * @access	public
	 * @param
	 * @return	$admin_labels
	 */
	public function getAdminLabels() {
		return $admin_labels = [
			'0'	=> '一般',
			'1'	=> '管理者'
		];
	}

	// ============================================================
	// 請求データ管理
	// ============================================================

	/**
	 * 一覧表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayRequestIndex() {
		global $smarty;

		// 期間選択の取得
		$start	= trimFull($_POST['start_month'] ?? '');	// 開始月
		$end	= trimFull($_POST['end_month'] ?? '');		// 終了月

		// ユーザーIDの取得
		$selected_user_id = trimFull($_POST['user_id'] ?? '');

		// 全員選択の判定
		$is_all_selected = ($selected_user_id == '');

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

		// ユーザーデータの取得
		$all_users = $this->dao->getAllUsers();

		// Smartyに渡す期間データの配列
		$period_info = [
			'start_month'	=> $period_data['start_month'] ?? '',
			'end_month'		=> $period_data['end_month'] ?? ''
		];

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		// 検索時はバリデーション実行
		$errors = [];
		if (! empty($start) || ! empty($end) || ! empty($selected_user_id)) {
			$errors = $this->validateRequest($start, $end, $selected_user_id);
		}

		// バリデーションに引っかかった場合
		if (! empty($errors)) {

			$smarty->assign([
				'errors'				=> $errors,
				'csrf_token'			=> $csrf_token,
				'routes'				=> $this->getLabels()['routes'],
				'types'					=> $this->getLabels()['types'],
				'start_month_options'	=> $start_month_options,
				'end_month_options'		=> $end_month_options,
				'js_month_options'		=> $js_month_options,
				'period_data'			=> $period_info,
				'all_users' 			=> $all_users,
				'selected_user_id'		=> $selected_user_id,
				'is_all_selected'		=> $is_all_selected,
				'section'				=> 'request'
			]);

			return $smarty->display('admin/request/index.tpl');
		}

		// 請求データとユーザーデータの取得
		$raw_data 	= $this->dao->getExpenses($period_data['from'], $period_data['to'], $selected_user_id);

		// 締め日に合わせた表示期間
		$period = [
			'from'		=> $this->_formatDateLabel($period_data['from']),	// 表示用：開始月日
			'to'		=> $this->_formatDateLabel($period_data['to']),		// 表示用：終了月日
			'from_raw'	=> $period_data['from'],							// 処理用：開始月日
			'to_raw'	=> $period_data['to']								// 処理用：終了月日
		];

		// 請求データの整形と集計
		$requests = $this->_formatRequests($raw_data);

		// Smartyに渡す請求データの配列
		$request_summary = [
			'request_data'	=> $requests['request_data'] ?? [],
			'total_fee'		=> $requests['total_fee'] ?? 0,
			'total_count'	=> $requests['total_count'] ?? 0
		];

		$smarty->assign([
			'csrf_token'			=> $csrf_token,
			'routes'				=> $this->getLabels()['routes'],
			'types'					=> $this->getLabels()['types'],
			'start_month_options'	=> $start_month_options,
			'end_month_options'		=> $end_month_options,
			'js_month_options'		=> $js_month_options,
			'period_data'			=> $period_info,
			'period'				=> $period,
			'requests'  			=> $request_summary,
			'all_users' 			=> $all_users,
			'selected_user_id'		=> $selected_user_id,
			'is_all_selected'		=> $is_all_selected,
			'section'				=> 'request'
		]);

		// 取消完了のリダイレクトフラグがあれば完了画面に遷移
		if (! empty($_SESSION['redirect_cancel'])) {

			// セッションクリア
			unset($_SESSION['redirect_cancel']);

			$smarty->assign([
				'layout'	=> 'admin/layout.tpl',
				'url'		=> '/expense/admin/',
			]);

			return $smarty->display('common/complete.tpl');
		}

		$smarty->display('admin/request/index.tpl');
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
				'value'	=> $target->format('Y-m'),	// value設定値
				'label'	=> $target->format('Y/n')	// 表示設定値
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
	 * 請求データの整形と集計
	 *
	 * @access	private
	 * @param	$raw_data
	 * @return	array
	 */
	private function _formatRequests($raw_data) {

		// 旅費請求データの表示形式調整
		$request_data = [];

		// 合計金額の初期値
		$total_fee = 0;

		foreach ($raw_data as $row) {
			$row['created_at']		= $this->_formatDateLabel($row['created_at']);
			$row['purchase_date']	= $this->_formatDateLabel($row['purchase_date']);

			// ユーザーID取得
			$user_id = $row['user_id'];

			// 初回ユーザーの初期化
			if (! isset($request_data[$user_id])) {

				$request_data[$user_id] = [
					'user_id'	=> $user_id,
					'user_name'	=> $row['user_name'],
					'items'		=> [],
					'sum_fee'	=> 0
				];
			}

			// データ追加
			$request_data[$user_id]['items'][] = $row;

			// is_activeが1の金額を加算
			if ($row['is_active'] == 1) {
				$total_fee							+= (int)$row['fee'];
				$request_data[$user_id]['sum_fee']	+= (int)$row['fee'];
			}
		}

		return [
			'request_data'	=> $request_data,
			'total_fee'		=> $total_fee
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
	 * @param	$start, $end, $selected_user_id
	 * @return	$error
	 */
	public function validateRequest($start, $end, $selected_user_id) {
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

		// 対象
		if ($selected_user_id != '') {
			if (! ctype_digit($selected_user_id) || strlen($selected_user_id) > 5 || str_starts_with($selected_user_id, '0')) {
				$errors['user'] = '対象の形式が正しくありません';
			} elseif (! $this->dao->isDuplicateId($selected_user_id)) {
				$errors['user'] = '選択された対象は存在しません';
			}
		}

		return $errors;
	}

	/**
	 * 取消確認：表示
	 *
	 * @access	public
	 * @param	$request_id, $errors = []
	 * @return
	 */
	public function displayRequestCancelForm($request_id, $errors = []) {
		global $smarty;

		// 請求データの取得
		$request = $this->dao->getExpenseById($request_id);

		// 請求データが見つからない場合はトップページにリダイレクト
		if (empty($request)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayRequestIndex();
		}

		$request_data = [
			'created_at'	=> $this->_formatDateLabel($request['created_at']),
			'purchase_date'	=> $this->_formatDateLabel($request['purchase_date']),
			'id'			=> $request['id'],
			'user_name'		=> $request['user_name'],
			'route'			=> $request['route_id'],
			'type'			=> $request['type_id'],
			'start'			=> $request['section_from'],
			'end'			=> $request['section_to'],
			'fee'			=> $request['fee'],
			'note'			=> $request['note'],
			'cancel_reason'	=> trimFull($_POST['cancel_reason'] ?? '')
		];

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'routes'		=> $this->getLabels()['routes'],
			'types'			=> $this->getLabels()['types'],
			'request'		=> $request_data,
			'errors'		=> $errors,
			'section'		=> 'request'
		]);

    	$smarty->display('admin/request/form.tpl');
	}

	/**
	 * 取消確認：完了
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayRequestCancelComplete() {

		// 取消確認の更新
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->_updateRequestCancel();

		// POST以外はトップページにリダイレクト
		} else {
			header('Location: /expense/admin/');
			exit;
		}
	}

	/**
	 * 取消確認：更新
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	private function _updateRequestCancel() {
		global $smarty;

		// POSTデータ取得
		$request_id		= trimFull($_POST['request_id'] ?? '');
		$cancel_reason	= trimFull($_POST['cancel_reason'] ?? '');

		// バリデーション実行
		$errors	= $this->validateRequestCancel($cancel_reason);

		// エラー内容を再描画
		if (! empty($errors)) {
			return $this->displayRequestCancelForm($request_id, $errors);
		}

		// データが不正な場合はトップページにリダイレクト
		if (! ctype_digit($request_id)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayRequestIndex();
		}

		// 請求データの取得
		$request = $this->dao->getExpenseById($request_id);

		// 請求データが見つからない場合はトップページにリダイレクト
		if (empty($request)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayRequestIndex();
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// データ更新：論理削除と取消理由
			$this->dao->updateExpenseCancel($request_id, $cancel_reason);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_cancel'] = true;

			// 完了画面にリダイレクト
			header('Location: /expense/admin/');
			exit;

		} catch (Exception $e) {
			return $this->displayRequestCancelForm($request_id);
		}
	}

	/**
	 * バリデーション：取消確認
	 *
	 * @access	public
	 * @param	$cancel_reason
	 * @return
	 */
	public function validateRequestCancel($cancel_reason) {
		$errors = [];

		if (empty($cancel_reason)) {
			$errors['cancel_reason'] = '取消理由を入力してください';
		} elseif (mb_strlen($cancel_reason) > 255) {
			$errors['cancel_reason'] = '取消理由は255文字以内で入力してください';
		}

		return $errors;
	}

	/**
	 * CSVダウンロード
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function downloadRequestCsv() {

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// 検索条件の取得
			$from		= trimFull($_POST['from'] ?? '');
			$to			= trimFull($_POST['to'] ?? '');
			$user_id	= trimFull($_POST['user_id'] ?? '');

			// 請求データ取得
			$expenses = $this->dao->getRequestCsv($from, $to, $user_id);

			// CSVデータをUTF-8で整形
			$csv_utf8 = $this->_buildRequestCsv($expenses);

			// CSVデータをSJISに変換
			$csv_sjis = mb_convert_encoding($csv_utf8, 'SJIS-win', 'UTF-8');

			// CSVファイル出力
			$filename = 'request_data_' . date('YmdHis') . '.csv';
			header('Content-Type: text/csv; charset=Shift_JIS');
			header("Content-Disposition: attachment; filename={$filename}");
			header('Content-Transfer-Encoding: binary');

			// セッションロックで連続ダウンロード対策
			session_write_close();

			echo $csv_sjis;
			exit;

		} catch (Exception $e) {
			return $this->displayRequestIndex();
		}
	}

	/**
	 * CSVデータ整形
	 *
	 * @access	private
	 * @param   $expenses
	 * @return  implode("\r\n", $lines)
	 */
	private function _buildRequestCsv($expenses) {

		// CSVヘッダー
		$lines = ['請求日,購入日,請求者,路線,種別,区間,料金,訪問先,状態,取消理由'];

		// 小計用変数
		$current_user_id 	= null;
		$total_fee			= 0;

		// ラベル用変数
		$routes = $this->getLabels()['routes'] ?? [];
		$types  = $this->getLabels()['types']  ?? [];

		// クロージャ：1行のデータ出力用
		$push = function ($line) {
			return implode(',', array_map(function ($val) {
				return '"' . str_replace('"', '""', $val) . '"';
			}, $line));
		};

		// 各行をCSV形式で追加
		foreach ($expenses as $row) {

			// 初回以外はユーザー毎に小計を出力
			if ($current_user_id !== null && $current_user_id != $row['user_id']) {

				// カンマ区切りで結合して追加
				$lines[] = $push(['小計','','','','','', number_format($total_fee),'','','']);

				// 小計をリセット
				$total_fee 	= 0;
			}

			// ユーザーID更新
			$current_user_id = $row['user_id'];

			// 取消を除いた金額を小計に加算
			if ($row['is_active'] == 1) {
				$total_fee += (int)$row['fee'];
			}

			// データ行
			$line = [
				$this->_formatDateLabel($row['created_at']),
				$this->_formatDateLabel($row['purchase_date']),
				$this->_sanitizeCsv($row['user_name']),
				$this->_sanitizeCsv($routes[$row['route_id']] ?? ''),
				$this->_sanitizeCsv($types[$row['type_id']] ?? ''),
				$this->_sanitizeCsv(($row['section_from'] ?? '') . ' 〜 ' . ($row['section_to'] ?? '')),
				$this->_sanitizeCsv(number_format($row['fee'])),
				$this->_sanitizeCsv($row['note'] ?? ''),
				$row['is_active'] == 0 ? '取消' : '',
				$this->_sanitizeCsv($row['cancel_reason'] ?? '')
			];

			// カンマ区切りで結合して追加
			$lines[] = $push($line);
		}

		// 最終ユーザーの小計を出力
		if ($current_user_id !== null) {
			$lines[] = $push(['小計','','','','','', number_format($total_fee),'','','']);
		}

		// 改行区切りで1つの文字列に
		return implode("\r\n", $lines);
	}

	/**
	 * CSVデータサニタイズ処理
	 *
	 * @access	private
	 * @param	$v
	 * @return	$v
	 */
	private function _sanitizeCsv($v) {

		// 0x00〜0x1Fの制御文字を除去して入力内容を文字列化
		$v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string)$v);

		// 先頭の空白を除いた判定用コピー
		$t = ltrim($v);

		// 数式開始防止
		if ($t !== '' && preg_match('/^[=\+\-@]/', $t)) {
			$v = "'" . $v;
		}

		// タブ/改行開始防止
		if ($v !== '' && preg_match('/^[\t\r\n]/', $v)) {
			$v = "'" . $v;
		}

		return $v;
	}

	// ============================================================
	// ユーザー管理
	// ============================================================

	/**
	 * 一覧表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayUserIndex() {
		global $smarty;

		// ユーザーデータの取得
		$users = $this->dao->getUsers();

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'users'			=> $users,
			'section'		=> 'user'
		]);

		// 完了のリダイレクトフラグがあれば完了画面に遷移
		if (! empty($_SESSION['redirect_complete'])) {

			// セッションクリア
			unset($_SESSION['redirect_complete']);

			$smarty->assign([
				'layout'	=> 'admin/layout.tpl',
				'url'		=> '/expense/admin/user/',
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

		$smarty->display('admin/user/index.tpl');
	}

	/**
	 * 登録：表示
	 *
	 * @access	public
	 * @param	$form_data = null, $errors = []
	 * @return
	 */
	public function displayUserEntryForm($form_data = null, $errors = []) {
		global $smarty;

		// フォームデータ取得
		if (empty($form_data)) {
			$form_data = $_SESSION['form_data'] ?? [
				'user_id'	=> '',
				'name'		=> '',
				'email'		=> '',
				'role'		=> ''
			];

			// セッションクリア
			unset($_SESSION['form_data']);
		}

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'form_data'		=> $form_data,
			'errors'		=> $errors,
			'section'		=> 'user'
		]);

		$smarty->display('admin/user/form.tpl');
	}

	/**
	 * 登録：確認
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayUserEntryConfirm() {
		global $smarty;

		// フォームデータ取得
		$form_data = [
			'user_id'			=> trimFull($_POST['user_id'] ?? ''),
			'name'				=> trimFull($_POST['name'] ?? ''),
			'email'				=> trimFull($_POST['email'] ?? ''),
			'password'			=> $_POST['password'] ?? '',
			'display_password'	=> str_repeat('*', strlen($_POST['password'])),
			'is_admin'			=> trimFull($_POST['is_admin'] ?? '')
		];

		// バリデーション実行
		$errors = $this->validateUser($form_data);

		// エラー内容を再描画
		if (! empty($errors)) {
			return $this->displayUserEntryForm($form_data, $errors);
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// セッションにフォームデータ保存
			$_SESSION['form_data'] = $form_data;

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'		=> $csrf_token,
				'form_data'			=> $form_data,
				// 'display_password'	=> str_repeat('*', strlen($form_data['password'])),
				'admin_labels'		=> $this->getAdminLabels(),
				'section'			=> 'user'
			]);

			$smarty->display('admin/user/confirm.tpl');

		} catch (Exception $e) {
			return $this->displayUserEntryForm();
		}
	}

	/**
	 * バリデーション：ユーザー
	 *
	 * @access	public
	 * @param	$course_name
	 * @return	$errors
	 */
	public function validateUser($form_data, $current_id = null) {
		$errors = [];

		// ユーザーID
		if (empty($form_data['user_id'])) {
			$errors['user_id'] = 'IDを入力してください';
		} elseif (! ctype_digit($form_data['user_id'])) {
			$errors['user_id'] = 'IDは半角数字で入力してください';
		} elseif (strlen($form_data['user_id']) > 5) {
			$errors['user_id'] = 'IDは5桁以内で入力してください';
		} elseif (str_starts_with($form_data['user_id'], '0')) {
			$errors['user_id'] = 'IDの先頭に0は使用できません';
		} elseif ($this->dao->isDuplicateId($form_data['user_id'], $current_id)) {
			$errors['user_id'] = 'このIDは既に使用されています';
		}

		// 氏名
		if (empty($form_data['name'])) {
			$errors['name'] = '氏名を入力してください';
		} elseif (mb_strlen($form_data['name']) > 40) {
			$errors['name'] = '氏名は40文字以内で入力してください';
		}

		// メールアドレス
		if (empty($form_data['email'])) {
			$errors['email'] = 'メールアドレスを入力してください';
		} elseif (! filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'メールアドレスの形式が正しくありません';
		} elseif ($this->dao->isDuplicateEmail($form_data['email'], $current_id)) {
			$errors['email'] = 'このメールアドレスは既に使用されています';
		}

		// パスワード（新規）
		if ($current_id === null && empty($form_data['password'])) {
			$errors['password'] = 'パスワードを入力してください';
		}

		// パスワード（入力）
		if (! empty($form_data['password'])) {
			if (! preg_match('/^[\x21-\x7E]+$/', $form_data['password'])) {
				$errors['password'] = 'パスワードは半角英数記号で入力してください';
			} elseif (strlen($form_data['password']) < 8) {
				$errors['password'] = 'パスワードは8文字以上で入力してください';
			} elseif (strlen($form_data['password']) > 64) {
				$errors['password'] = 'パスワードが長すぎます';
			}
		}

		// 権限
		if (! isset($form_data['is_admin']) || $form_data['is_admin'] == '') {
			$errors['is_admin'] = '権限を選択してください';
		} elseif (! in_array($form_data['is_admin'], ['0', '1'], true)) {
			$errors['is_admin'] = '権限の値が不正です';
		}

		return $errors;
	}

	/**
	 * 登録：完了
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayUserEntryComplete() {

		// ユーザー登録
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->_registUser();

		// POST以外はトップページにリダイレクト
		} else {
			header('Location: /expense/admin/user/');
			exit;
		}
	}

	/**
	 * ユーザー登録
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	private function _registUser() {
		global $smarty;

		// セッションのフォームデータ取得
		$form_data = $_SESSION['form_data'] ?? [];

		// セッションクリア
		unset($_SESSION['form_data']);

		// フォームデータがない場合はトップページにリダイレクト
		if (empty($form_data)) {
			header('Location: /expense/admin/user/');
			exit;
		}

		try {
			// デモユーザー共通ガード
			$this->guardDemo();

			// CSRFトークン検証
			$this->validateCsrfToken();

			// パスワードをハッシュ化
			$form_data['password'] = password_hash($form_data['password'], PASSWORD_DEFAULT);

			// ユーザー登録
			$this->dao->insertUser($form_data);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_complete'] = true;

			// 完了画面にリダイレクト
			header('Location: /expense/admin/user/');
			exit;

		} catch (Exception $e) {

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'form_data'		=> $form_data,
				'admin_labels'	=> $this->getAdminLabels(),
				'section'		=> 'user'
			]);

			$smarty->display('admin/user/confirm.tpl');
		}
	}

	/**
	 * 修正：表示
	 *
	 * @access	public
	 * @param	$id, $form_data = null, $errors = []
	 * @return
	 */
	public function displayUserEditForm($id, $form_data = null, $errors = []) {
		global $smarty;

		// ユーザーデータ取得
		$user = $this->dao->getUserById($id);

		// ユーザーが見つからない場合はトップページリダイレクト
		if (empty($user) && empty($errors)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayUserIndex();
		}

		// フォームデータ取得
		if (empty($form_data)) {
			$form_data = $_SESSION['form_data'] ?? [
				'user_id'	=> $user['id'],
				'name'		=> $user['name'],
				'email'		=> $user['email'],
				'is_admin'	=> $user['is_admin']
			];

			// セッションクリア
			unset($_SESSION['form_data']);
		}

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'form_data'		=> $form_data,
			'original_id'	=> $id,
			'errors'		=> $errors,
			'section'		=> 'user'
		]);

		$smarty->display('admin/user/edit_form.tpl');
	}

	/**
	 * 修正：確認
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayUserEditConfirm() {
		global $smarty;

		// フォームデータ取得
		$form_data = [
			'user_id'			=> trimFull($_POST['user_id'] ?? ''),
			'name'				=> trimFull($_POST['name'] ?? ''),
			'email'				=> trimFull($_POST['email'] ?? ''),
			'password'			=> $_POST['password'] ?? '',
			'display_password'	=> (! empty($_POST['password']) ? str_repeat('*', strlen($_POST['password'])) : '変更なし'),
			'is_admin'			=> trimFull($_POST['is_admin'] ?? '')
		];

		// チェック用ユーザーID取得
		$original_id = trimFull($_POST['original_id'] ?? '');

		// ユーザーIDが空か不正の場合はトップページにリダイレクト
		if (! ctype_digit($original_id)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayUserIndex();
		}

		// バリデーション実行
		$errors = $this->validateUser($form_data, $original_id);

		// エラー内容を再描画
		if (! empty($errors)) {
			return $this->displayUserEditForm($original_id, $form_data, $errors);
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
				'form_data'		=> $form_data,
				'admin_labels'	=> $this->getAdminLabels(),
				'original_id'	=> $original_id,
				'section'		=> 'user'
			]);

			$smarty->display('admin/user/edit_confirm.tpl');

		} catch (Exception $e) {
			return $this->displayUserEditForm($original_id);
		}
	}

	/**
	 * 修正：完了
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayUserEditComplete() {

		// ユーザー更新
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->_updateUser();

		// POST以外はトップページにリダイレクト
		} else {
			header('Location: /expense/admin/user/');
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
	private function _updateUser() {
		global $smarty;

		// セッションデータ取得
		$edit_data = $_SESSION['form_data'] ?? null;

		// セッションクリア
		unset($_SESSION['form_data']);

		// データが不正な場合はトップページにリダイレクト
		if (empty($edit_data)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayUserIndex();
		}

		// チェック用ユーザーID取得
		$original_id = trimFull($_POST['original_id'] ?? '');

		// ユーザーデータ取得
		$user = $this->dao->getUserById($original_id);

		// ユーザーデータが見つからない場合はトップページにリダイレクト
		if (empty($user)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayUserIndex();
		}

		try {
			// デモユーザー共通ガード
			$this->guardDemo();

			// CSRFトークン検証
			$this->validateCsrfToken();

			// パスワードをハッシュ化
			if (! empty($edit_data['password'])) {
				$edit_data['password'] = password_hash($edit_data['password'], PASSWORD_DEFAULT);
			}

			// ユーザー更新
			$this->dao->updateUser($edit_data, $original_id);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_complete'] = true;

			// 完了画面にリダイレクト
			header('Location: /expense/admin/user/');
			exit;

		} catch (Exception $e) {

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'form_data'		=> $edit_data,
				'admin_labels'	=> $this->getAdminLabels(),
				'original_id'	=> $original_id,
				'section'		=> 'user'
			]);

			return $smarty->display('admin/user/edit_confirm.tpl');
		}
	}

	/**
	 * 削除
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function deleteUsers() {

		// 削除対象のユーザーID取得
		$user_ids = array_map('trimFull', $_POST['user'] ?? ['']);

		// データが不正な場合はトップページにリダイレクト
		if (empty($user_ids) || ! is_array($user_ids)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayUserIndex();
		}

		// IDが不正な場合はトップページにリダイレクト
		if (! $this->dao->existsUserIds($user_ids)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayUserIndex();
		}

		try {
			// デモユーザー共通ガード
			$this->guardDemo();

			// CSRFトークン検証
			$this->validateCsrfToken();

			// ユーザーの削除
			$this->dao->deleteUsers($user_ids);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_deleted'] = true;

			// トップページにリダイレクト
			header('Location: /expense/admin/user/');
			exit;

		} catch (Exception $e) {
			return $this->displayUserIndex();
		}
	}

	// ============================================================
	// メール宛先管理
	// ============================================================

	/**
	 * 一覧表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayMailIndex() {
		global $smarty;

		// メールデータの取得
		$mails = $this->dao->getMails();

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'mails'			=> $mails,
			'section'		=> 'mail'
		]);

		// 完了のリダイレクトフラグがあれば完了画面に遷移
		if (! empty($_SESSION['redirect_complete'])) {

			// セッションクリア
			unset($_SESSION['redirect_complete']);

			$smarty->assign([
				'layout'	=> 'admin/layout.tpl',
				'url'		=> '/expense/admin/mail/',
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

		$smarty->display('admin/mail/index.tpl');
	}

	/**
	 * 登録：表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayMailEntryForm($form_data = null, $errors = []) {
		global $smarty;

		// フォームデータ取得
		if (empty($form_data)) {
			$form_data	= $_SESSION['form_data'] ?? [
				'email'			=> '',
				'sort_order'	=> ''
			];

			// セッションクリア
			unset($_SESSION['form_data']);
		}

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'form_data'		=> $form_data,
			'errors'		=> $errors,
			'section'		=> 'mail'
		]);

		$smarty->display('admin/mail/form.tpl');
	}

	/**
	 * 登録：確認
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayMailEntryConfirm() {
		global $smarty;

		// フォームデータ取得
		$form_data = [
			'email'	=> trimFull($_POST['email'] ?? '')
		];

		// バリデーション実行
		$errors = $this->validateMail($form_data);

		// エラー内容を再描画
		if (! empty($errors)) {
			return $this->displayMailEntryForm($form_data, $errors);
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
				'form_data'		=> $form_data,
				'section'		=> 'mail'
			]);

			$smarty->display('admin/mail/confirm.tpl');

		} catch (Exception $e) {
			return $this->displayMailEntryForm();
		}
	}

	/**
	 * バリデーション：メール
	 *
	 * @access	public
	 * @param	$course_name
	 * @return	$errors
	 */
	public function validateMail($form_data, $current_id = null) {
		$errors = [];

		if (empty($form_data['email'])) {
			$errors['email'] = 'メールアドレスを入力してください';
		} elseif (! filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'メールアドレスの形式が正しくありません';
		} elseif ($this->dao->isDuplicateRecipient($form_data['email'], $current_id)) {
			$errors['email'] = 'このメールアドレスは既に使用されています';
		}

		return $errors;
	}

	/**
	 * 登録：完了
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayMailEntryComplete() {

		// メール登録
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->_registMail();

		// POST以外はトップページにリダイレクト
		} else {
			header('Location: /expense/admin/mail/');
			exit;
		}
	}

	/**
	 * メール登録
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	private function _registMail() {
		global $smarty;

		// セッションのフォームデータ取得
		$form_data = $_SESSION['form_data'] ?? [];

		// セッションクリア
		unset($_SESSION['form_data']);

		// フォームデータがない場合はトップページにリダイレクト
		if (empty($form_data)) {
			header('Location: /expense/admin/mail/');
			exit;
		}

		try {
			// デモユーザー共通ガード
			$this->guardDemo();

			// CSRFトークン検証
			$this->validateCsrfToken();

			// メール登録
			$this->dao->insertMail($form_data);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_complete'] = true;

			// 完了画面にリダイレクト
			header('Location: /expense/admin/mail/');
			exit;

		} catch (Exception $e) {

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'form_data'		=> $form_data,
				'section'		=> 'mail'
			]);

			$smarty->display('admin/mail/confirm.tpl');
		}
	}

	/**
	 * 修正：表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayMailEditForm($id, $form_data = null, $errors = []) {
		global $smarty;

		// メールデータ取得
		$mail = $this->dao->getMailById($id);

		// メールが見つからない場合はトップページにリダイレクト
		if (empty($mail)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayMailIndex();
		}

		// フォームデータ取得
		if (empty($form_data)) {
			$form_data = $_SESSION['form_data'] ?? [
				'mail_id'	=> $mail['id'],
				'email'		=> $mail['email']
			];

			// セッションクリア
			unset($_SESSION['form_data']);
		}

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'form_data'		=> $form_data,
			'errors'		=> $errors,
			'section'		=> 'mail'
		]);

		$smarty->display('admin/mail/edit_form.tpl');
	}

	/**
	 * 修正：確認
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayMailEditConfirm() {
		global $smarty;

		// フォームデータ取得
		$form_data = [
			'mail_id'	=> trimFull($_POST['mail_id'] ?? ''),
			'email'		=> trimFull($_POST['email'] ?? '')
		];

		// メールID取得
		$mail_id = trimFull($_POST['mail_id'] ?? '');

		// メールIDが不正の場合はトップページにリダイレクト
		if (! ctype_digit($mail_id)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayMailIndex();
		}

		// バリデーション実行
		$errors = $this->validateMail($form_data, $mail_id);

		// エラー内容を再描画
		if (! empty($errors)) {
			return $this->displayMailEditForm($mail_id, $form_data, $errors);
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
				'form_data'		=> $form_data,
				'section'		=> 'mail'
			]);

			$smarty->display('admin/mail/edit_confirm.tpl');

		} catch (Exception $e) {
			return $this->displayMailEditForm($mail_id);
		}
	}

	/**
	 * 修正：完了
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayMailEditComplete() {

		// メール更新
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->_updateMail();

		// POST以外はトップページにリダイレクト
		} else {
			header('Location: /expense/admin/mail/');
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
	private function _updateMail() {
		global $smarty;

		// セッションデータ取得
		$edit_data = $_SESSION['form_data'] ?? null;

		// セッションクリア
		unset($_SESSION['form_data']);

		// データが不正な場合はトップページにリダイレクト
		if (empty($edit_data)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayMailIndex();
		}

		// メールID取得
		$mail_id = trimFull($_POST['mail_id'] ?? '');

		// メールデータ取得
		$mail = $this->dao->getMailById($mail_id);

		// メールが見つからない場合はトップページにリダイレクト
		if (empty($mail)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayMailIndex();
		}

		try {
			// デモユーザー共通ガード
			$this->guardDemo();

			// CSRFトークン検証
			$this->validateCsrfToken();

			// メール更新
			$this->dao->updateMail($edit_data);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_complete'] = true;

			// 完了画面にリダイレクト
			header('Location: /expense/admin/mail/');
			exit;

		} catch (Exception $e) {

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'form_data'		=> $edit_data,
				'section'		=> 'mail'
			]);

			return $smarty->display('admin/mail/edit_confirm.tpl');
		}
	}

	/**
	 * 削除
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function deleteMails() {

		// 削除対象のメールID取得
		$mail_ids = array_map('trimFull', $_POST['mail'] ?? ['']);

		// データが不正な場合はトップページにリダイレクト
		if (empty($mail_ids) || ! is_array($mail_ids)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayMailIndex();
		}

		// IDが不正な場合はトップページにリダイレクト
		if (! $this->dao->existsMailIds($mail_ids)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayMailIndex();
		}

		try {
			// デモユーザー共通ガード
			$this->guardDemo();

			// CSRFトークン検証
			$this->validateCsrfToken();

			// メールの削除
			$this->dao->deleteMails($mail_ids);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_deleted'] = true;

			// トップページにリダイレクト
			header('Location: /expense/admin/mail/');
			exit;

		} catch (Exception $e) {
			return $this->displayMailIndex();
		}
	}

	/**
	 * 並び替え：表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayMailSortForm() {
		global $smarty;

		// 並び替え順更新
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && ! empty($_POST['sort_ids'])) {
			return $this->_updateMailSortOrder();
		}

		// メールデータ取得
		$mails = $this->dao->getMails();

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'mails'			=> $mails,
			'section'		=> 'mail'
		]);

		$smarty->display('admin/mail/sort.tpl');
	}

	/**
	 * 並び替え：更新
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	private function _updateMailSortOrder() {
		global $smarty;

		// JSで生成した並び替え順のID一覧を取得
		$sort_ids = array_map('trimFull', $_POST['sort_ids'] ?? ['']);

		// データが不正な場合はトップページにリダイレクト
		if (empty($sort_ids) || ! is_array($sort_ids)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayMailIndex();
		}

		try {
			// デモユーザー共通ガード
			$this->guardDemo();

			// CSRFトークン検証
			$this->validateCsrfToken();

			// 並び替え順の更新
			$this->dao->updateMailSortOrder($sort_ids);

			// トップページにリダイレクト
			header('Location: /expense/admin/mail/');
			exit;

		} catch (Exception $e) {

			// メールデータ取得
			$mails = $this->dao->getMails();

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'mails'			=> $mails,
				'section'		=> 'mail'
			]);

			$smarty->display('admin/mail/sort.tpl');
		}
	}

	// ============================================================
	// 路線管理
	// ============================================================

	/**
	 * 一覧表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayRouteIndex() {
		global $smarty;

		// 路線データの取得
		$routes = $this->dao->getRoutes();

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'routes'		=> $routes,
			'section'		=> 'route'
		]);

		// 完了のリダイレクトフラグがあれば完了画面に遷移
		if (! empty($_SESSION['redirect_complete'])) {

			// セッションクリア
			unset($_SESSION['redirect_complete']);

			$smarty->assign([
				'layout'	=> 'admin/layout.tpl',
				'url'		=> '/expense/admin/route/',
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

		$smarty->display('admin/route/index.tpl');
	}

	/**
	 * 登録：表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayRouteEntryForm($form_data = null, $errors = []) {
		global $smarty;

		// フォームデータ取得
		if (empty($form_data)) {
			$form_data = $_SESSION['form_data'] ?? [
				'route'			=> '',
				'sort_order'	=> ''
			];

			// セッションクリア
			unset($_SESSION['form_data']);
		}

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'form_data'		=> $form_data,
			'errors'		=> $errors,
			'section'		=> 'route'
		]);

		$smarty->display('admin/route/form.tpl');
	}

	/**
	 * 登録：確認
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayRouteEntryConfirm() {
		global $smarty;

		// フォームデータ取得
		$form_data = [
			'route' => trimFull($_POST['route'] ?? '')
		];

		// バリデーション実行
		$errors = $this->validateRoute($form_data);

		// エラー内容を再描画
		if (! empty($errors)) {
			return $this->displayRouteEntryForm($form_data, $errors);
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
				'form_data'		=> $form_data,
				'section'		=> 'route'
			]);

			$smarty->display('admin/route/confirm.tpl');

		} catch (Exception $e) {
			return $this->displayRouteEntryForm();
		}
	}

	/**
	 * バリデーション：路線
	 *
	 * @access	public
	 * @param	$course_name
	 * @return	$errors
	 */
	public function validateRoute($form_data, $current_id = null) {
		$errors = [];

		if (empty($form_data['route'])) {
			$errors['route'] = '路線を入力してください';
		} elseif (mb_strlen($form_data['route']) > 40) {
			$errors['route'] = '路線は40文字以内で入力してください';
		} elseif ($this->dao->isDuplicateRoute($form_data['route'], $current_id)) {
			$errors['route'] = 'この路線は既に使用されています';
		}

		return $errors;
	}

	/**
	 * 登録：完了
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayRouteEntryComplete() {

		// 路線登録
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->_registRoute();

		// POST以外はトップページにリダイレクト
		} else {
			header('Location: /expense/admin/route/');
			exit;
		}
	}

	/**
	 * 路線登録
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	private function _registRoute() {
		global $smarty;

		// セッションのフォームデータ取得
		$form_data = $_SESSION['form_data'] ?? [];

		// セッションクリア
		unset($_SESSION['form_data']);

		// フォームデータがない場合はトップページにリダイレクト
		if (empty($form_data)) {
			header('Location: /expense/admin/route/');
			exit;
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// 路線登録
			$this->dao->insertRoute($form_data);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_complete'] = true;

			// 完了画面にリダイレクト
			header('Location: /expense/admin/route/');
			exit;

		} catch (Exception $e) {

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'form_data'		=> $form_data,
				'section'		=> 'route'
			]);

			$smarty->display('admin/route/confirm.tpl');
		}
	}

	/**
	 * 修正：表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayRouteEditForm($id, $form_data = null, $errors = []) {
		global $smarty;

		// 路線データ取得
		$route = $this->dao->getRouteById($id);

		// 路線が見つからない場合はトップページにリダイレクト
		if (empty($route)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayRouteIndex();
		}

		// フォームデータ取得
		if (empty($form_data)) {
			$form_data = $_SESSION['form_data'] ?? [
				'route_id'	=> $route['id'],
				'route' 	=> $route['route_name']
			];

			// セッションクリア
			unset($_SESSION['form_data']);
		}

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'form_data'		=> $form_data,
			'errors'		=> $errors,
			'section'		=> 'route'
		]);

		$smarty->display('admin/route/edit_form.tpl');
	}

	/**
	 * 修正：確認
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayRouteEditConfirm() {
		global $smarty;

		// フォームデータ取得
		$form_data = [
			'route_id'	=> trimFull($_POST['route_id'] ?? ''),
			'route'		=> trimFull($_POST['route'] ?? '')
		];

		// 路線ID取得
		$route_id = trimFull($_POST['route_id'] ?? '');

		// 路線IDが不正の場合はトップページにリダイレクト
		if (! ctype_digit($route_id)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayRouteIndex();
		}

		// バリデーション実行
		$errors = $this->validateRoute($form_data, $route_id);

		// エラー内容を再描画
		if (! empty($errors)) {
			return $this->displayRouteEditForm($route_id, $form_data, $errors);
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
				'form_data'		=> $form_data,
				'section'		=> 'route'
			]);

			$smarty->display('admin/route/edit_confirm.tpl');

		} catch (Exception $e) {
			return $this->displayRouteEditForm($route_id);
		}
	}

	/**
	 * 修正：完了
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayRouteEditComplete() {

		// 路線更新
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->_updateRoute();

		// POST以外はトップページにリダイレクト
		} else {
			header('Location: /expense/admin/route/');
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
	private function _updateRoute() {
		global $smarty;

		// セッションデータ取得
		$edit_data = $_SESSION['form_data'] ?? null;

		// セッションクリア
		unset($_SESSION['form_data']);

		// データが不正な場合はトップページにリダイレクト
		if (empty($edit_data)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayRouteIndex();
		}

		// 路線ID取得
		$route_id = trimFull($_POST['route_id'] ?? '');

		// 路線データ取得
		$route = $this->dao->getRouteById($route_id);

		// 路線データが見つからない場合はトップページにリダイレクト
		if (empty($route)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayRouteIndex();
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// 路線更新
			$this->dao->updateRoute($edit_data);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_complete'] = true;

			// 完了画面にリダイレクト
			header('Location: /expense/admin/route/');
			exit;

		} catch (Exception $e) {

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'form_data'		=> $edit_data,
				'section'		=> 'route'
			]);

			$smarty->display('admin/route/edit_confirm.tpl');
		}
	}

	/**
	 * 削除
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function deleteRoutes() {

		// 削除対象の路線ID取得
		$route_ids = array_map('trimFull', $_POST['route'] ?? ['']);

		// データが不正な場合はトップページにリダイレクト
		if (empty($route_ids) || ! is_array($route_ids)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayRouteIndex();
		}

		// IDが不正な場合はトップページにリダイレクト
		if (! $this->dao->existsRouteIds($route_ids)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayRouteIndex();
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// 路線の削除
			$this->dao->deleteRoutes($route_ids);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_deleted'] = true;

			// トップページにリダイレクト
			header('Location: /expense/admin/route/');
			exit;

		} catch (Exception $e) {
			return $this->displayRouteIndex();
		}
	}

	/**
	 * 並び替え：表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function sortRoutes() {
		global $smarty;

		// 並び替え順更新
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && ! empty($_POST['sort_ids'])) {
			return $this->_updateRouteSortOrder();
		}

		// 路線データ取得
		$routes = $this->dao->getRoutes();

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'routes'		=> $routes,
			'section'		=> 'route'
		]);

		$smarty->display('admin/route/sort.tpl');
	}

	/**
	 * 並び替え：更新
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	private function _updateRouteSortOrder() {
		global $smarty;

		// JSで生成した並び替え順のID一覧を取得
		$sort_ids = array_map('trimFull', $_POST['sort_ids'] ?? ['']);

		// データが不正な場合はトップページにリダイレクト
		if (empty($sort_ids) || ! is_array($sort_ids)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayRouteIndex();
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// 並び替え順の更新
			$this->dao->updateRouteSortOrder($sort_ids);

			// トップページにリダイレクト
			header('Location: /expense/admin/route/');
			exit;

		} catch (Exception $e) {

			// 路線データ取得
			$routes = $this->dao->getRoutes();

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'routes'		=> $routes,
				'section'		=> 'route'
			]);

			$smarty->display('admin/route/sort.tpl');
		}
	}

	// ============================================================
	// 種別管理
	// ============================================================

	/**
	 * 一覧表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayTypeIndex() {
		global $smarty;

		// 種別データの取得
		$types = $this->dao->getTypes();

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'types'			=> $types,
			'section'		=> 'type'
		]);

		// 完了のリダイレクトフラグがあれば完了画面に遷移
		if (! empty($_SESSION['redirect_complete'])) {

			// セッションクリア
			unset($_SESSION['redirect_complete']);

			$smarty->assign([
				'layout'	=> 'admin/layout.tpl',
				'url'		=> '/expense/admin/type/',
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

		$smarty->display('admin/type/index.tpl');
	}

	/**
	 * 登録：表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayTypeEntryForm($form_data = null, $errors = []) {
		global $smarty;

		// フォームデータ取得
		if (empty($form_data)) {
			$form_data = $_SESSION['form_data'] ?? [
				'type'			=> '',
				'sort_order' 	=> ''
			];

			// セッションクリア
			unset($_SESSION['form_data']);
		}

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'form_data'		=> $form_data,
			'errors'		=> $errors,
			'section'		=> 'type'
		]);

		$smarty->display('admin/type/form.tpl');
	}

	/**
	 * 登録：確認
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayTypeEntryConfirm() {
		global $smarty;

		// フォームデータ取得
		$form_data = [
			'type' => trimFull($_POST['type'] ?? '')
		];

		// バリデーション実行
		$errors = $this->validateType($form_data);

		// エラー内容を再描画
		if (! empty($errors)) {
			return $this->displayTypeEntryForm($form_data, $errors);
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
				'form_data'		=> $form_data,
				'section'		=> 'type'
			]);

			$smarty->display('admin/type/confirm.tpl');

		} catch (Exception $e) {
			return $this->displayTypeEntryForm();
		}
	}

	/**
	 * バリデーション：種別
	 *
	 * @access	public
	 * @param	$course_name
	 * @return	$errors
	 */
	public function validateType($form_data, $current_id = null) {
		$errors = [];

		if (empty($form_data['type'])) {
			$errors['type'] = '種別を入力してください';
		} elseif (mb_strlen($form_data['type']) > 40) {
			$errors['type'] = '種別は40文字以内で入力してください';
		} elseif ($this->dao->isDuplicateType($form_data['type'], $current_id)) {
			$errors['type'] = 'この種別は既に使用されています';
		}

		return $errors;
	}

	/**
	 * 登録：完了
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayTypeEntryComplete() {

		// 種別登録
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->_registType();

		// POST以外はトップページにリダイレクト
		} else {
			header('Location: /expense/admin/type/');
			exit;
		}
	}

	/**
	 * 種別登録
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	private function _registType() {
		global $smarty;

		// セッションのフォームデータ取得
		$form_data = $_SESSION['form_data'] ?? [];

		// セッションクリア
		unset($_SESSION['form_data']);

		// フォームデータがない場合はトップページにリダイレクト
		if (empty($form_data)) {
			header('Location: /expense/admin/type/');
			exit;
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// 種別登録
			$this->dao->insertType($form_data);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_complete'] = true;

			// 完了画面にリダイレクト
			header('Location: /expense/admin/type/');
			exit;

		} catch (Exception $e) {

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'form_data'		=> $form_data,
				'section'		=> 'type'
			]);

			$smarty->display('admin/type/confirm.tpl');
		}
	}

	/**
	 * 修正：表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayTypeEditForm($id, $form_data = null, $errors = []) {
		global $smarty;

		// 種別データ取得
		$type = $this->dao->getTypeById($id);

		// 種別が見つからない場合はトップページにリダイレクト
		if (empty($type)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayTypeIndex();
		}

		// フォームデータ取得
		if (empty($form_data)) {
			$form_data = $_SESSION['form_data'] ?? [
				'type_id'	=> $type['id'],
				'type'		=> $type['type_name']
			];

			// セッションクリア
			unset($_SESSION['form_data']);
		}

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'form_data'		=> $form_data,
			'errors'		=> $errors,
			'section'		=> 'type'
		]);

		$smarty->display('admin/type/edit_form.tpl');
	}

	/**
	 * 修正：確認
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayTypeEditConfirm() {
		global $smarty;

		// フォームデータ取得
		$form_data = [
			'type_id'	=> trimFull($_POST['type_id'] ?? ''),
			'type'		=> trimFull($_POST['type'] ?? '')
		];

		// 種別ID取得
		$type_id = trimFull($_POST['type_id'] ?? '');

		// 種別IDが不正の場合はトップページにリダイレクト
		if (! ctype_digit($type_id)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayTypeIndex();
		}

		// バリデーション実行
		$errors = $this->validateType($form_data, $type_id);

		// エラー内容を再描画
		if (! empty($errors)) {
			return $this->displayTypeEditForm($type_id, $form_data, $errors);
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
				'form_data'		=> $form_data,
				'section'		=> 'type'
			]);

			$smarty->display('admin/type/edit_confirm.tpl');

		} catch (Exception $e) {
			return $this->displayTypeEditForm($type_id);
		}
	}

	/**
	 * 修正：完了
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function displayTypeEditComplete() {

		// 種別更新
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return $this->_updateType();

		// POST以外はトップページにリダイレクト
		} else {
			header('Location: /expense/admin/type/');
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
	private function _updateType() {
		global $smarty;

		// セッションデータ取得
		$edit_data = $_SESSION['form_data'] ?? null;

		// セッションクリア
		unset($_SESSION['form_data']);

		// データが不正な場合はトップページにリダイレクト
		if (empty($edit_data)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayTypeIndex();
		}

		// 種別ID取得
		$type_id = trimFull($_POST['type_id'] ?? '');

		// 種別データ取得
		$type = $this->dao->getTypeById($type_id);

		// 種別データが見つからない場合はトップページにリダイレクト
		if (empty($type)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayTypeIndex();
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// 種別更新
			$this->dao->updateType($edit_data);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_complete'] = true;

			// 完了画面にリダイレクト
			header('Location: /expense/admin/type/');
			exit;

		} catch (Exception $e) {

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'form_data'		=> $edit_data,
				'section'		=> 'type'
			]);

			$smarty->display('admin/type/edit_confirm.tpl');
		}
	}

	/**
	 * 削除
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function deleteTypes() {

		// 削除対象の種別ID取得
		$type_ids = array_map('trimFull', $_POST['type'] ?? ['']);

		// データが不正な場合はトップページにリダイレクト
		if (empty($type_ids) || ! is_array($type_ids)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayTypeIndex();
		}

		// IDが不正な場合はトップページにリダイレクト
		if (! $this->dao->existsTypeIds($type_ids)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayTypeIndex();
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// 種別の削除
			$this->dao->deleteTypes($type_ids);

			// セッションにリダイレクトフラグ保存
			$_SESSION['redirect_deleted'] = true;

			// トップページにリダイレクト
			header('Location: /expense/admin/type/');
			exit;

		} catch (Exception $e) {
			return $this->displayTypeIndex();
		}
	}

	/**
	 * 並び替え：表示
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	public function sortTypes() {
		global $smarty;

		// 並び替え順更新
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && ! empty($_POST['sort_ids'])) {
			return $this->_updateTypeSortOrder();
		}

		// 種別データ取得
		$types = $this->dao->getTypes();

		// Smartyに渡すCSRFトークンの生成
		$csrf_token = $this->generateCsrfToken();

		$smarty->assign([
			'csrf_token'	=> $csrf_token,
			'types'			=> $types,
			'section'		=> 'type'
		]);

		$smarty->display('admin/type/sort.tpl');
	}

	/**
	 * 並び替え：更新
	 *
	 * @access	public
	 * @param
	 * @return
	 */
	private function _updateTypeSortOrder() {
		global $smarty;

		// JSで生成した並び替え順のID一覧を取得
		$sort_ids = array_map('trimFull', $_POST['sort_ids'] ?? ['']);

		// データが不正な場合はトップページにリダイレクト
		if (empty($sort_ids) || ! is_array($sort_ids)) {
			trigger_error(ERROR_USER_INVALID, E_USER_WARNING);
			return $this->displayTypeIndex();
		}

		try {
			// CSRFトークン検証
			$this->validateCsrfToken();

			// 並び替え順の更新
			$this->dao->updateTypeSortOrder($sort_ids);

			// トップページにリダイレクト
			header('Location: /expense/admin/type/');
			exit;

		} catch (Exception $e) {

			// 種別データ取得
			$types = $this->dao->getTypes();

			// Smartyに渡すCSRFトークンの生成
			$csrf_token = $this->generateCsrfToken();

			$smarty->assign([
				'csrf_token'	=> $csrf_token,
				'types'			=> $types,
				'section'		=> 'type'
			]);

			$smarty->display('admin/type/sort.tpl');
		}
	}
}
