{extends file="user/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section class="course-confirm">
			<div class="heading u-mb-24">
				<h2 class="heading__title u-mb-16">内容確認</h2>
				<p class="heading__text">
					こちらの内容で申請してよろしいですか？<br>
					問題がなければ「登録する」をクリックしてください
				</p>
			</div>

			{* 全体エラー *}
			{if $warning}
				{foreach from=$warning item=msg}
					<p class="error-text u-mb-8">{$msg}</p>
				{/foreach}
			{/if}

			<form action="/expense/course/" method="POST">

				{* 画面遷移 *}
				<input type="hidden" name="state" value="complete">

				{* CSRFトークン *}
				<input type="hidden" name="csrf_token" value="{$csrf_token}">

				{foreach from=$form_data.course_name key=num item=course_name}
					<div class="form-confirm__row u-mb-12">
						<div class="form-confirm__row-title">
							No.<span class="form-confirm__row-number">{$num + 1}</span>
						</div>
						<dl class="form-confirm__group">

							{* コース名 *}
							<div class="form-confirm__field">
								<dt class="form-confirm__label">コース名</dt>
								<dd>{$form_data.course_name[$num]}</dd>
							</div>

							{* 路線 *}
							<div class="form-confirm__field">
								<dt class="form-confirm__label">路線</dt>
								<dd>{$routes[$form_data.route[$num]]}</dd>
							</div>

							{* 種別 *}
							<div class="form-confirm__field">
								<dt class="form-confirm__label">種別</dt>
								<dd>{$types[$form_data.type[$num]]}</dd>
							</div>

							{* 区間 *}
							<div class="form-confirm__field">
								<dt class="form-confirm__label">区間</dt>
								<dd>{$form_data.start[$num]} 〜 {$form_data.end[$num]}</dd>
							</div>

							{* 料金 *}
							<div class="form-confirm__field">
								<dt class="form-confirm__label">料金</dt>
								<dd>{$form_data.fee[$num]|number_format}円</dd>
							</div>

							{* 訪問先 *}
							<div class="form-confirm__field">
								<dt class="form-confirm__label">訪問先</dt>
								<dd>{$form_data.note[$num]}</dd>
							</div>
						</dl>
					</div>
				{/foreach}

				{* ボタン群 *}
				<div class="button__wrap u-mt-24">
					<button type="button" class="button button--sub js-entry-button">戻る</button>
			    	<button type="submit" class="button button--main">登録する</button>
			    </div>
			</form>

			{* 戻る *}
			<form action="/expense/course/" method="POST" class="js-entry-form" style="display: none;">
				<input type="hidden" name="state" value="form">
			</form>
		</section>
	</main>
{/block}
