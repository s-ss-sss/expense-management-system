{extends file="admin/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section>
			<div class="heading u-mb-24">
				<h2 class="heading__title u-mb-16">内容確認</h2>
				<p class="heading__text">
					こちらの内容で登録してよろしいですか？<br>
					問題がなければ「登録する」をクリックしてください
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
				<input type="hidden" name="state" value="complete">

				{* CSRFトークン *}
				<input type="hidden" name="csrf_token" value="{$csrf_token}">

				{* 路線ID *}
				<input type="hidden" name="type_id" value="{$form_data.type_id}">

				<dl class="form-block__group">
					<div class="form-block__field">
						<dt class="form-block__label">種別</dt>
						<dd class="form-block__input-wrap">{$form_data.type}</dd>
					</div>
				</dl>

				{* ボタン群 *}
				<div class="button__wrap u-mt-24">
					<button type="button" class="button button--sub js-entry-button">戻る</button>
					<button type="submit" class="button button--main">登録する</button>
				</div>
			</form>

			{* 戻る *}
			<form action="/expense/admin/type/edit/{$form_data.type_id}/" method="POST" class="js-entry-form" style="display: none;">
				<input type="hidden" name="state" value="form">
			</form>
		</section>
	</main>
{/block}
