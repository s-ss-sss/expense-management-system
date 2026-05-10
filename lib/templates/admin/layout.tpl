<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<title>{$SITE_NAME}</title>
	<meta name="description" content="{$SITE_NAME}の管理画面です。">
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
							<a href="/expense/admin/" class="header__nav-link"{if $section == 'request'} aria-current="page"{/if}>請求データ管理</a>
						</li>
						<li class="header__nav-item">
							<a href="/expense/admin/user/" class="header__nav-link"{if $section == 'user'} aria-current="page"{/if}>ユーザー管理</a>
						</li>
						<li class="header__nav-item">
							<a href="/expense/admin/mail/" class="header__nav-link"{if $section == 'mail'} aria-current="page"{/if}>メール宛先管理</a>
						</li>
						<li class="header__nav-item">
							<a href="/expense/admin/route/" class="header__nav-link"{if $section == 'route'} aria-current="page"{/if}>路線管理</a>
						</li>
						<li class="header__nav-item">
							<a href="/expense/admin/type/" class="header__nav-link"{if $section == 'type'} aria-current="page"{/if}>種別管理</a>
						</li>
						<li class="header__nav-item">
							<a href="/expense/" class="header__nav-link">一般メニュー</a>
						</li>
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
