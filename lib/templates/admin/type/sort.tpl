{extends file="admin/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section>
			<div class="heading u-mb-24">
				<h2 class="heading__title u-mb-16">種別管理</h2>
				<p class="heading__text">
					並び替えは下記のリストをドラッグして<br>
					「保存する」をクリックしてください
				</p>
			</div>
			
			{* 全体エラー *}
			{if $warning}
				{foreach from=$warning item=msg}
					<p class="error-text u-mb-8">{$msg}</p>
				{/foreach}
			{/if}
			
			<form action="/expense/admin/type/" method="POST" class="js-sort-form">
				
				{* 画面遷移 *}
				<input type="hidden" name="state" value="sort">
				
				{* CSRFトークン *}
				<input type="hidden" name="csrf_token" value="{$csrf_token}">
				
				<ul class="sort">
					{foreach from=$types item=type}
						<li class="sort__list" data-id="{$type.id}">
							<span class="sort__handle"></span>
							<p class="sort__item">{$type.type_name}</p>
						</li>
					{/foreach}
				</ul>
				
				{* ボタン群 *}
				<div class="button__wrap u-mt-24">
					<a href="/expense/admin/type/" class="button button--sub">戻る</a>
					<button type="button" class="button button--sub js-undo-button">元に戻す</button>
					<button type="submit" class="button button--main">保存する</button>
				</div>
			</form>
		</section>
	</main>
{/block}