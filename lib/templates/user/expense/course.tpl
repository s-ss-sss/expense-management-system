{extends file="user/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section class="confirm-course">
			<div class="heading u-mb-24">
				<h2 class="heading__title u-mb-16">申請完了</h2>
				<p class="heading__text">
					今回の内容を【よく使うコース】に登録しますか？<br>
					登録する場合は「No.」をチェックして「コース名」を入力後<br>
					「登録する」をクリックしてください
				</p>
			</div>

			{* 全体エラー *}
			{if $warning}
				{foreach from=$warning item=msg}
					<p class="error-text u-mb-8">{$msg}</p>
				{/foreach}
			{/if}

			<form action="/expense/" method="POST">

				{* 画面遷移 *}
				<input type="hidden" name="state" value="complete">

				{* CSRFトークン *}
				<input type="hidden" name="csrf_token" value="{$csrf_token}">

				{foreach from=$form_data.date key=num item=date}
					<div class="form-confirm__row-wrap u-mb-12">
						<div class="form-confirm__row u-border-bottom-dashed">
							<div class="form-confirm__row-title">
								<input
									type="checkbox"
									id="course-{$num}"
									name="course_register[]"
									class="form-confirm__row-checkbox {if isset($errors[$num].checkbox)}error-form{/if}"
									value="{$num}"
									{if in_array($num, $form_data.course_register)}checked{/if}
								>
								<label for="course-{$num}" class="form-confirm__row-label">
									No.<span class="form-confirm__row-number">{$num + 1}</span>
								</label>
							</div>
							<dl class="form-confirm__group">

								{* 購入日 *}
								<div class="form-confirm__field">
									<dt class="form-confirm__label">購入日</dt>
									<dd>
										{$form_data.date[$num]}
										<input type="hidden" name="date[]" value="{$form_data.date[$num]}">
									</dd>
								</div>

								{* 路線 *}
								<div class="form-confirm__field">
									<dt class="form-confirm__label">路線</dt>
									<dd>
										{$routes[$form_data.route[$num]]}
										<input type="hidden" name="route[]" value="{$form_data.route[$num]}">
									</dd>
								</div>

								{* 種別 *}
								<div class="form-confirm__field">
									<dt class="form-confirm__label">種別</dt>
									<dd>
										{$types[$form_data.type[$num]]}
										<input type="hidden" name="type[]" value="{$form_data.type[$num]}">
									</dd>
								</div>

								{* 区間 *}
								<div class="form-confirm__field">
									<dt class="form-confirm__label">区間</dt>
									<dd>
										{$form_data.start[$num]} 〜 {$form_data.end[$num]}
										<input type="hidden" name="start[]" value="{$form_data.start[$num]}">
										<input type="hidden" name="end[]" value="{$form_data.end[$num]}">
									</dd>
								</div>

								{* 料金 *}
								<div class="form-confirm__field">
									<dt class="form-confirm__label">料金</dt>
									<dd>
										{$form_data.fee[$num]|number_format}円
										<input type="hidden" name="fee[]" value="{$form_data.fee[$num]}">
									</dd>
								</div>

								{* 訪問先 *}
								<div class="form-confirm__field">
									<dt class="form-confirm__label">訪問先</dt>
									<dd>
										{$form_data.note[$num]}
										<input type="hidden" name="note[]" value="{$form_data.note[$num]}">
									</dd>
								</div>
							</dl>
						</div>
						<div class="form-confirm__course">
							<label class="form-confirm__course-label">コース名</label>
							<div class="form-confirm__course-field">
								<input
									type="text"
									name="course_name[]"
									class="form-confirm__course-input {if isset($errors[$num].course_name)}error-form{/if}"
									value="{$form_data.course_name[$num]|default:''}"
								>
							</div>
						</div>
						{if isset($errors[$num].checkbox)}
							<p class="error-text u-mt-8">{$errors[$num].checkbox}</p>
						{elseif isset($errors[$num].course_name)}
							<p class="error-text u-mt-8">{$errors[$num].course_name}</p>
						{/if}
					</div>
				{/foreach}

				{* 未入力で登録ボタンを押した場合のエラー表示 *}
				{if isset($errors.common)}
					<p class="error-text u-mt-8">{$errors.common}</p>
				{/if}

				{* ボタン群 *}
				<div class="button__wrap u-mt-24">
					<a href="/expense/" class="button button--sub">登録しない</a>
			    	<button type="submit" class="button button--main">登録する</button>
			    </div>
			</form>
		</section>
	</main>
{/block}
