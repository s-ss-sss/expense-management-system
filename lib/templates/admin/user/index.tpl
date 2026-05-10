{extends file="admin/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section>
			
			{if ! empty($users)}
				<div class="heading u-mb-24">
					<h2 class="heading__title u-mb-16">ユーザー管理</h2>
					<p class="heading__text">
						削除する場合は「選択」をチェック後<br>
						「削除する」をクリックしてください
					</p>
				</div>
				
				{* 全体エラー *}
				{if $warning}
					{foreach from=$warning item=msg}
						<p class="error-text u-mb-8">{$msg}</p>
					{/foreach}
				{/if}
				
				<form action="/expense/admin/user/" method="POST">
					
					{* 削除 *}
					<input type="hidden" name="state" value="delete">
					
					{* CSRFトークン *}
					<input type="hidden" name="csrf_token" value="{$csrf_token}">
					
					{* ユーザーテーブル *}
					<div class="table__wrap u-mb-24">
						<table class="table">
							<colgroup>
								<col style="width: 50px;">
								<col style="width: auto;">
								<col style="width: auto;">
								<col style="width: auto;">
								<col style="width: auto;">
								<col style="width: auto;">
							</colgroup>
							<thead class="table__thead">
								<tr class="table__tr">
									<th class="table__th">選択</th>
									<th class="table__th">ID</th>
									<th class="table__th">氏名</th>
									<th class="table__th">メールアドレス</th>
									<th class="table__th">権限</th>
									<th class="table__th table__th--action">操作</th>
								</tr>
							</thead>
							<tbody class="table__tbody">
								{foreach from=$users item=user}
									<tr class="table__tr">
										<td class="table__td table__td--checkbox">
											<label class="table__checkbox-label">
												<input type="checkbox" name="user[]" value="{$user.id}" class="table__checkbox js-delete-check">
											</label>
										</td>
										<td class="table__td">{$user.id}</td>
										<td class="table__td">{$user.name}</td>
										<td class="table__td">{$user.email}</td>
										<td class="table__td">{$user.is_admin}</td>
										<td class="table__td table__td--action">
											<a href="/expense/admin/user/edit/{$user.id}/" class="button button--sub">修正する</a>
										</td>
									</tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				
					{* ボタン群 *}
					<div class="button__wrap">
						<button type="submit" class="button button--red js-delete-button">削除する</button>
						<button type="button" class="button button--main js-entry-button">新規登録する</button>
					</div>
				</form>
			{else}
				<p class="course-list__no-item">ユーザーは登録されていません</p>
				<div class="button__wrap u-mt-24">
					<button type="button" class="button button--main js-entry-button">新規登録する</button>
			    </div>
			{/if}
			
			{* 登録 *}
			<form action="/expense/admin/user/" method="POST" class="js-entry-form" style="display: none;">
				<input type="hidden" name="state" value="form">
			</form>
		</section>
	</main>
{/block}

{block name=footer_script}
	{include file="common/alert.tpl"}
{/block}