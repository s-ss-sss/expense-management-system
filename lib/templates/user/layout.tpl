<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<title>{$SITE_NAME}</title>
	<meta name="description" content="{$SITE_NAME}のユーザー画面です。">
	<link rel="stylesheet" href="{$BASE_URL}css/style.css" type="text/css">
</head>
<body>
	<div class="u-container">

		{* ヘッダー *}
		<header class="header u-mb-24">
			<div class="header__inner">
				<h1 class="header__title u-mb-12">旅費請求</h1>
				<nav class="header__nav u-inner" aria-label="メインメニュー">
					<ul class="header__nav-list">
						<li class="header__nav-item">
							<a href="/expense/" class="header__nav-link"{if $section == 'expense'} aria-current="page"{/if}>旅費請求</a>
						</li>
						<li class="header__nav-item">
							<a href="/expense/history/" class="header__nav-link"{if $section == 'history'} aria-current="page"{/if}>履歴確認</a>
						</li>
						<li class="header__nav-item">
							<a href="/expense/course/" class="header__nav-link"{if $section == 'course'} aria-current="page"{/if}>設定</a>
						</li>
						{if $is_admin == '1'}
							<li class="header__nav-item">
								<a href="/expense/admin/" class="header__nav-link">管理メニュー</a>
							</li>
						{/if}
					</ul>
					<div class="header__nav-user">
						<span class="header__nav-name">{$user_name}</span>
						<a href="/expense/logout/" class="header__nav-logout">ログアウト</a>
					</div>
				</nav>
			</div>
		</header>

		{* メイン *}
		<div class="u-inner">
			{block name=content}{/block}
		</div>

		{* フッター *}
		<footer class="footer">
			<small class="footer__copy">Copyright &copy; Expense Demo All rights reserved．</small>
		</footer>
	</div>
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
	<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
	<script src="{$BASE_URL}js/datepicker-ja.js" type="text/javascript"></script>
	<script src="{$BASE_URL}js/script.js" type="text/javascript"></script>
	{block name=footer_script}{/block}
</body>
</html>
