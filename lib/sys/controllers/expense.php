<?php

require_once ROOT_PATH . '/lib/sys/modules/daos/ExpenseDao.class.php';
require_once ROOT_PATH . '/lib/sys/modules/Expense.class.php';

// 依存性注入でCommonに渡す
$expenseDao	= new ExpenseDao($db);
$expense	= new Expense($expenseDao);

$mode	= getPathInfo(0) ?: 'default';
$action	= getPathInfo(1);
$id		= getPathInfo(2);
$state	= $_POST['state'] ?? 'default';

// ログイン時とログアウト時以外はチェック
if (! in_array($mode, ['login', 'logout'])) {
	$expense->checkLogin();
	$expense->checkSessionTimeout();
	$expense->setAdminFlag();
}

switch ($mode) {

	// ログイン
	case 'login':
		validPathInfo(1);
		$expense->displayLogin();
		break;

	// ログアウト
	case 'logout':
		validPathInfo(1);
		$expense->logout();
		break;

	// 旅費請求
	default:
		validPathInfo(1);
		switch ($state) {

			// 確認
			case 'confirm':
				$expense->displayExpenseConfirm();
				break;

			// よく使うコース
			case 'course':
				$expense->displayExpenseCourse();
				break;

			// 完了
			case 'complete':
				$expense->displayExpenseComplete();
				break;

			// 一覧
			default:
				$expense->displayExpenseForm();
				break;
		}
		break;

	// 履歴確認
	case 'history':
		validPathInfo(1);
		$expense->displayHistory();
		break;

	// よく使うコース
	case 'course':
		switch ($action) {

			// 修正
			case 'edit':
				validPathInfo(3);
				switch ($state) {

					// 確認
					case 'confirm':
						$expense->displayCourseEditConfirm();
						break;

					// 完了
					case 'complete':
						$expense->displayCourseEditComplete();
						break;

					// フォーム
					default:
						$expense->displayCourseEditForm($id);
						break;
				}
				break;

			default:
				validPathInfo(1);
				switch ($state) {

					// フォーム
					case 'form':
						$expense->displayCourseEntryForm();
						break;

					// 確認
					case 'confirm':
						$expense->displayCourseEntryConfirm();
						break;

					// 完了
					case 'complete':
						$expense->displayCourseEntryComplete();
						break;

					// 削除
					case 'delete':
						$expense->deleteCourse();
						break;

					// 一覧
					default:
						$expense->displayCourseIndex();
						break;
				}
				break;
		}
		break;
}
