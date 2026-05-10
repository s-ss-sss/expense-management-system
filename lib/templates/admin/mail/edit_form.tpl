{extends file="admin/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section>
			<div class="heading u-mb-24">
				<h2 class="heading__title u-mb-16">メール宛先管理</h2>
				<p class="heading__text">
					修正する場合は内容を入力後<br>
					「確認画面へ」をクリックしてください
				</p>
			</div>
			
			{* 全体エラー *}
			{if $warning}
				{foreach from=$warning item=msg}
					<p class="error-text u-mb-8">{$msg}</p>
				{/foreach}
			{/if}
			
			<form action="/expense/admin/mail/edit/{$form_data.mail_id}/" method="POST">
				
				{* 画面遷移 *}
				<input type="hidden" name="state" value="confirm">
				
				{* CSRFトークン *}
				<input type="hidden" name="csrf_token" value="{$csrf_token}">
				
				{* メールID *}
				<input type="hidden" name="mail_id" value="{$form_data.mail_id}">
				
				<div class="form-block__group">
					<div class="form-block__field">
						<label class="form-block__label">メールアドレス</label>
						<div class="form-block__input-wrap">
							<input
								type="text"
								name="email"
								class="form-block__input {if isset($errors.email)}error-form{/if}"
								value="{$form_data.email}"
							>
							{if isset($errors.email)}
								<p class="error-text u-mt-8">{$errors.email}</p>
							{/if}
						</div>
					</div>
				</div>
				
				{* ボタン群 *}
				<div class="button__wrap u-mt-24">
					<a href="/expense/admin/mail/" class="button button--sub">戻る</a>
					<button type="submit" class="button button--main">確認画面へ</button>
				</div>
			</form>
		</section>
	</main>
{/block}