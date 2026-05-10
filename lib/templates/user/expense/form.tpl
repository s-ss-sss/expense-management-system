{extends file="user/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section class="form">
			<div class="heading u-mb-24">
				<h2 class="heading__title u-mb-16">旅費請求</h2>
				<p class="heading__text">
					申請する場合は内容を入力後<br>
					「確認画面へ」をクリックしてください
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
				<input type="hidden" name="state" value="confirm">

				{* CSRFトークン *}
				<input type="hidden" name="csrf_token" value="{$csrf_token}">

				{section name=row loop=$row_count}
					{assign var="num" value=$smarty.section.row.index}
					<div class="form-list__row u-mb-12" data-index="{$num + 1}">
						<div class="form-list__row-title">
							No.<span class="form-list__row-number">{$num + 1}</span>
						</div>
						<div class="form-list__main">
							<div class="form-list__group">

								{* 購入日 *}
								<div class="form-list__field">
									<label class="form-list__label">購入日</label>
									<div class="datepicker-wrap">
										<input
											type="text"
											name="date[]"
											class="form-list__input js-datepicker {if isset($errors[$num].date)}error-form{/if}"
											value="{$form_data.date[$num]}"
											readonly
										>
									</div>
									{if isset($errors[$num].date)}
										<p class="error-text u-mt-8">{$errors[$num].date}</p>
									{/if}
								</div>

								{* 路線 *}
								<div class="form-list__field">
									<label class="form-list__label">路線</label>
									<select name="route[]" class="form-list__select {if isset($errors[$num].route)}error-form{/if}">
										<option value="">選択してください</option>
										{foreach from=$routes key=key item=label}
											<option value="{$key}" {if $form_data.route[$num] == $key}selected{/if}>{$label}</option>
										{/foreach}
									</select>
									{if isset($errors[$num].route)}
										<p class="error-text u-mt-8">{$errors[$num].route}</p>
									{/if}
								</div>

								{* 種別 *}
								<div class="form-list__field">
									<label class="form-list__label">種別</label>
									<select name="type[]" class="form-list__select {if isset($errors[$num].type)}error-form{/if}">
										<option value="">選択してください</option>
										{foreach from=$types key=key item=label}
											<option value="{$key}" {if $form_data.type[$num] == $key}selected{/if}>{$label}</option>
										{/foreach}
									</select>
									{if isset($errors[$num].type)}
										<p class="error-text u-mt-8">{$errors[$num].type}</p>
									{/if}
								</div>

								{* 区間 *}
								<div class="form-list__field">
									<label class="form-list__label">区間</label>
									<div class="form-list__range">
										<input
											type="text"
											name="start[]"
											class="form-list__input {if isset($errors[$num].start)}error-form{/if}"
											value="{$form_data.start[$num]}"
										>
										<span class="form-list__delimiter">〜</span>
										<input
											type="text"
											name="end[]"
											class="form-list__input {if isset($errors[$num].end)}error-form{/if}"
											value="{$form_data.end[$num]}"
										>
									</div>
									{if isset($errors[$num].section)}
										<p class="error-text u-mt-8">{$errors[$num].section}</p>
									{/if}
								</div>

								{* 料金 *}
								<div class="form-list__field">
									<label class="form-list__label">料金</label>
									<div class="form-list__unit-wrap">
										<input
											type="text"
											name="fee[]"
											class="form-list__input form-list__input--number {if isset($errors[$num].fee)}error-form{/if}"
											value="{$form_data.fee[$num]}"
										>
										<span class="form-list__unit">円</span>
									</div>
									{if isset($errors[$num].fee)}
										<p class="error-text u-mt-8">{$errors[$num].fee}</p>
									{/if}
								</div>

								{* 訪問先 *}
								<div class="form-list__field">
									<label class="form-list__label">訪問先</label>
									<input
										type="text"
										name="note[]"
										class="form-list__input {if isset($errors[$num].note)}error-form{/if}"
										value="{$form_data.note[$num]}"
									>
									{if isset($errors[$num].note)}
										<p class="error-text u-mt-8">{$errors[$num].note}</p>
									{/if}
								</div>
							</div>

							{* ボタン群 *}
							<div class="form-list__action">
								<button type="button" class="button button--main js-open-modal" aria-label="コース選択">コース選択</button>
								<button type="button" class="button button--remove" aria-label="行を削除">行を削除</button>
							</div>
						</div>
					</div>
				{/section}

				{* 行を追加ボタン *}
				<div class="form-list__add u-mb-24">
					<button type="button" class="button button--add" aria-label="行を追加">行を追加</button>
				</div>

				{* ボタン群 *}
				<div class="button__wrap">
					<button type="button" class="button button--sub js-clear-form">内容をクリア</button>
					<button type="submit" class="button button--main">確認画面へ</button>
				</div>
			</form>
		</section>

		{* モーダル *}
		<div id="course-modal" class="modal" style="display: none;">
			<div class="modal__overlay"></div>
			<div class="modal__content">
				<div class="modal__close"></div>
				<h2 class="modal__title u-mb-24">よく使うコース一覧</h2>
				<ul class="modal__list">
					{foreach from=$course_lists item=course_list}
						<li
							class="modal__item"
							data-route_id="{$course_list.route_id}"
							data-type_id="{$course_list.type_id}"
							data-section_from="{$course_list.section_from}"
							data-section_to="{$course_list.section_to}"
							data-fee="{$course_list.fee}"
							data-note="{$course_list.note}"
						>
							{$course_list.course_name}
						</li>
					{foreachelse}
						<li class="modal__no-item">よく使うコースは登録されていません</li>
					{/foreach}
				</ul>
			</div>
		</div>

	</main>
{/block}
