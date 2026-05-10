{extends file="user/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section class="course-edit">
			<div class="heading u-mb-24">
				<h2 class="heading__title u-mb-16">よく使うコース</h2>
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
			
			<form action="/expense/course/edit/{$form_data.id}/" method="POST">
				
				{* 画面遷移 *}
				<input type="hidden" name="state" value="confirm">
				
				{* CSRFトークン *}
				<input type="hidden" name="csrf_token" value="{$csrf_token}">
				
				{* コースID *}
				<input type="hidden" name="course_id" value="{$form_data.id}">
				
				<div class="form-list__row u-mb-24">
					<div class="form-list__row-title">修正</div>
					<div class="form-list__main">
						<div class="form-list__group">
							
							{* コース名 *}
							<div class="form-list__field">
								<label class="form-list__label">コース名</label>
								<input
									type="text"
									name="course_name"
									class="form-list__input {if isset($errors.course_name)}error-form{/if}" 
									value="{$form_data.course_name}"
								>
								{if isset($errors.course_name)}
									<p class="error-text u-mt-8">{$errors.course_name}</p>
								{/if}
							</div>
							
							{* 路線 *}
							<div class="form-list__field">
								<label class="form-list__label">路線</label>
								<select name="route" class="form-list__select {if isset($errors.route)}error-form{/if}">
									<option value="">選択してください</option>
								    {foreach from=$routes key=key item=label}
								        <option value="{$key}" {if $form_data.route == $key}selected{/if}>{$label}</option>
								    {/foreach}
								</select>
								{if isset($errors.route)}
									<p class="error-text u-mt-8">{$errors.route}</p>
								{/if}
							</div>
							
							{* 種別 *}
							<div class="form-list__field">
								<label class="form-list__label">種別</label>
								<select name="type" class="form-list__select {if isset($errors.type)}error-form{/if}">
									<option value="">選択してください</option>
								    {foreach from=$types key=key item=label}
								        <option value="{$key}" {if $form_data.type == $key}selected{/if}>{$label}</option>
								    {/foreach}
								</select>
								{if isset($errors.type)}
									<p class="error-text u-mt-8">{$errors.type}</p>
								{/if}
							</div>
														
							{* 区間 *}
							<div class="form-list__field">
								<label class="form-list__label">区間</label>
								<div class="form-list__range">
									<input
										type="text"
										name="start"
										class="form-list__input {if isset($errors.start)}error-form{/if}"
										value="{$form_data.start}"
									>
									<span class="form-list__delimiter">〜</span>
									<input
										type="text"
										name="end"
										class="form-list__input {if isset($errors.end)}error-form{/if}"
										value="{$form_data.end}"
									>
								</div>
								{if isset($errors.section)}
									<p class="error-text u-mt-8">{$errors.section}</p>
								{/if}
							</div>
							
							{* 料金 *}
							<div class="form-list__field">
								<label class="form-list__label">料金</label>
								<div class="form-list__unit-wrap">
									<input
										type="text"
										name="fee"
										class="form-list__input form-list__input--number {if isset($errors.fee)}error-form{/if}" 
										value="{$form_data.fee}"
									>
									<span class="form-list__unit">円</span>
								</div>
								{if isset($errors.fee)}
									<p class="error-text u-mt-8">{$errors.fee}</p>
								{/if}
							</div>
							
							{* 訪問先 *}
							<div class="form-list__field">
								<label class="form-list__label">訪問先</label>
								<input
									type="text"
									name="note"
									class="form-list__input {if isset($errors.note)}error-form{/if}"
									value="{$form_data.note}"
								>
								{if isset($errors.note)}
									<p class="error-text u-mt-8">{$errors.note}</p>
								{/if}
							</div>
						</div>
					</div>
				</div>
				
				{* ボタン群 *}
				<div class="button__wrap">
					<a href="/expense/course/" class="button button--sub">戻る</a>
					<button type="submit" class="button button--main">確認画面へ</button>
				</div>
			</form>
		</section>
	</main>
{/block}