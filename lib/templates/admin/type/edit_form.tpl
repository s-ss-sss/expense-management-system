{extends file="admin/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section>
			<div class="heading u-mb-24">
				<h2 class="heading__title u-mb-16">種別管理</h2>
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
			
			<form action="/expense/admin/type/edit/{$form_data.type_id}/" method="POST">
				
				{* 画面遷移 *}
				<input type="hidden" name="state" value="confirm">
				
				{* CSRFトークン *}
				<input type="hidden" name="csrf_token" value="{$csrf_token}">
				
				{* メールID *}
				<input type="hidden" name="type_id" value="{$form_data.type_id}">
				
				<div class="form-block__group">
					<div class="form-block__field">
						<label class="form-block__label">種別</label>
						<div class="form-block__input-wrap">
							<input
								type="text"
								name="type"
								class="form-block__input {if isset($errors.type)}error-form{/if}"
								value="{$form_data.type}"
							>
							{if isset($errors.type)}
								<p class="error-text u-mt-8">{$errors.type}</p>
							{/if}
						</div>
					</div>
				</div>
				
				{* ボタン群 *}
				<div class="button__wrap u-mt-24">
					<a href="/expense/admin/type/" class="button button--sub">戻る</a>
					<button type="submit" class="button button--main">確認画面へ</button>
				</div>
			</form>
		</section>
	</main>
{/block}