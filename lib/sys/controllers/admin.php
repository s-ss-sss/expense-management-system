<?php

require_once ROOT_PATH . '/lib/sys/modules/daos/AdminDao.class.php';
require_once ROOT_PATH . '/lib/sys/modules/Admin.class.php';

// 依存性注入でCommonに渡す
$adminDao	= new AdminDao($db);
$admin		= new Admin($adminDao);

$mode	= getPathInfo(0) ?: 'default';
$action	= getPathInfo(1);
$id		= getPathInfo(2);
$state	= $_POST['state'] ?? 'default';

// ログアウト時以外はチェック
if (! in_array($mode, ['logout'])) {
	$admin->checkLogin();
	$admin->checkSessionTimeout();
	$admin->setAdminFlag();
}

switch ($mode) {

	// ログアウト
	case 'logout':
		validPathInfo(1);
		$admin->logout();
		break;

	// 請求データ管理
	case 'request':
	default:
		switch ($action) {

			// 取消確認
			case 'cancel':
				validPathInfo(3);
				switch ($state) {

					// 完了
					case 'complete':
						$admin->displayRequestCancelComplete();
						break;

					// フォーム
					default:
						$admin->displayRequestCancelForm($id);
						break;
				}
				break;

			// 一覧
			default:
				validPathInfo(1);
				switch ($state) {

					// CSVダウンロード
					case 'download':
						$admin->downloadRequestCsv();
						break;

					// 一覧
					default:
						$admin->displayRequestIndex();
						break;
				}
				break;
		}
		break;

	// ユーザー管理
	case 'user':
		switch ($action) {

			// 修正
			case 'edit':
				validPathInfo(3);
				switch ($state) {

					// 確認
					case 'confirm':
						$admin->displayUserEditConfirm();
						break;

					// 完了
					case 'complete':
						$admin->displayUserEditComplete();
						break;

					// フォーム
					default:
						$admin->displayUserEditForm($id);
						break;
				}
				break;

			default:
				validPathInfo(1);
				switch ($state) {

					// フォーム
					case 'form':
						$admin->displayUserEntryForm();
						break;

					// 確認
					case 'confirm':
						$admin->displayUserEntryConfirm();
						break;

					// 完了
					case 'complete':
						$admin->displayUserEntryComplete();
						break;

					// 削除
					case 'delete':
						$admin->deleteUsers();
						break;

					// 一覧
					default:
						$admin->displayUserIndex();
						break;
				}
				break;
		}
		break;

	// メール宛先管理
	case 'mail':
		switch ($action) {

			// 修正
			case 'edit':
				validPathInfo(3);
				switch ($state) {

					// 確認
					case 'confirm':
						$admin->displayMailEditConfirm();
						break;

					// 完了
					case 'complete':
						$admin->displayMailEditComplete();
						break;

					// フォーム
					default:
						$admin->displayMailEditForm($id);
						break;
				}
				break;

			default:
				validPathInfo(1);
				switch ($state) {

					// フォーム
					case 'form':
						$admin->displayMailEntryForm();
						break;

					// 確認
					case 'confirm':
						$admin->displayMailEntryConfirm();
						break;

					// 完了
					case 'complete':
						$admin->displayMailEntryComplete();
						break;

					// 削除
					case 'delete':
						$admin->deleteMails();
						break;

					// 並び替え
					case 'sort':
						$admin->displayMailSortForm();
						break;

					// 一覧
					default:
						$admin->displayMailIndex();
						break;
				}
				break;
		}
		break;

	// 路線管理
	case 'route':
		switch ($action) {

			// 修正
			case 'edit':
				validPathInfo(3);
				switch ($state) {

					// 確認
					case 'confirm':
						$admin->displayRouteEditConfirm();
						break;

					// 完了
					case 'complete':
						$admin->displayRouteEditComplete();
						break;

					// フォーム
					default:
						$admin->displayRouteEditForm($id);
						break;
				}
				break;

			default:
				validPathInfo(1);
				switch ($state) {

					// フォーム
					case 'form':
						$admin->displayRouteEntryForm();
						break;

					// 確認
					case 'confirm':
						$admin->displayRouteEntryConfirm();
						break;

					// 完了
					case 'complete':
						$admin->displayRouteEntryComplete();
						break;

					// 削除
					case 'delete':
						$admin->deleteRoutes();
						break;

					// 並び替え
					case 'sort':
						$admin->sortRoutes();
						break;

					// 一覧
					default:
						$admin->displayRouteIndex();
						break;
				}
				break;
		}
		break;

	// 種別管理
	case 'type':
		switch ($action) {

			// 修正
			case 'edit':
				validPathInfo(3);
				switch ($state) {

					// 確認
					case 'confirm':
						$admin->displayTypeEditConfirm();
						break;

					// 完了
					case 'complete':
						$admin->displayTypeEditComplete();
						break;

					// フォーム
					default:
						$admin->displayTypeEditForm($id);
						break;
				}
				break;

			default:
				validPathInfo(1);
				switch ($state) {

					// フォーム
					case 'form':
						$admin->displayTypeEntryForm();
						break;

					// 確認
					case 'confirm':
						$admin->displayTypeEntryConfirm();
						break;

					// 完了
					case 'complete':
						$admin->displayTypeEntryComplete();
						break;

					// 削除
					case 'delete':
						$admin->deleteTypes();
						break;

					// 並び替え
					case 'sort':
						$admin->sortTypes();
						break;

					// 一覧
					default:
						$admin->displayTypeIndex();
						break;
				}
				break;
		}
		break;
}
