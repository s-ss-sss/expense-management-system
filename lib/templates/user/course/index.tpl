{extends file="user/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section class="course-list">
			
			{if ! empty($course_lists)}
				<div class="heading u-mb-24">
					<h2 class="heading__title u-mb-16">よく使うコース</h2>
					<p class="heading__text">
						削除する場合は「No.」をチェック後<br>
						「削除する」をクリックしてください
					</p>
				</div>
				
				{* 全体エラー *}
				{if $warning}
					{foreach from=$warning item=msg}
						<p class="error-text u-mb-8">{$msg}</p>
					{/foreach}
				{/if}
				
				<form action="/expense/course/" method="POST">
					
					{* 削除 *}
					<input type="hidden" name="state" value="delete">
					
					{* CSRFトークン *}
					<input type="hidden" name="csrf_token" value="{$csrf_token}">
					
					{foreach from=$course_lists key=num item=course_list}
						<div class="form-confirm__row-wrap u-mb-12">
							<div class="form-confirm__row u-border-bottom-dashed" data-index="1">
								<div class="form-confirm__row-title">
									<input
										type="checkbox"
										id="course-{$num}"
										name="course_register[]"
										value="{$course_list.id}"
										class="form-confirm__row-checkbox js-delete-check"
									>
									<label for="course-{$num}" class="form-confirm__row-label">
										No.<span class="form-confirm__row-number">{$num + 1}</span>
									</label>
								</div>
								<dl class="form-confirm__group">
									
									{* コース *}
									<div class="form-confirm__field">
										<dt class="form-confirm__label">コース名</dt>
										<dd>{$course_list.course_name}</dd>
									</div>
									
									{* 路線 *}
									<div class="form-confirm__field">
										<dt class="form-confirm__label">路線</dt>
										<dd>{$routes[$course_list.route_id]}</dd>
									</div>
									
									{* 種別 *}
									<div class="form-confirm__field">
										<dt class="form-confirm__label">種別</dt>
										<dd>{$types[$course_list.type_id]}</dd>
									</div>
									
									{* 区間 *}
									<div class="form-confirm__field">
										<dt class="form-confirm__label">区間</dt>
										<dd>{$course_list.section_from} 〜 {$course_list.section_to}</dd>
									</div>
									
									{* 料金 *}
									<div class="form-confirm__field">
										<dt class="form-confirm__label">料金</dt>
										<dd>{$course_list.fee|number_format}円</dd>
									</div>
			
									{* 訪問先 *}
									<div class="form-confirm__field">
										<dt class="form-confirm__label">訪問先</dt>
										<dd>{$course_list.note}</dd>
									</div>
								</dl>
							</div>
							<div class="form-confirm__course">
								<label class="form-confirm__course-label">操作</label>
								<div class="form-confirm__course-field">
									<a href="/expense/course/edit/{$course_list.id}" class="button button--sub">修正する</a>
								</div>	
							</div>
						</div>
					{/foreach}
					
					{if isset($errors.delete)}
						<p class="error-text u-mt-8">{$errors.delete}</p>
					{/if}
					
					{* ボタン群 *}
					<div class="button__wrap u-mt-24">
						<button type="submit" class="button button--red js-delete-button">削除する</button>
						<button type="button" class="button button--main js-entry-button">新規登録する</button>
				    </div>
				</form>
			{else}
				<p class="course-list__no-item">よく使うコースは登録されていません</p>
				<div class="button__wrap u-mt-24">
					<button type="button" class="button button--main js-entry-button">新規登録する</button>
			    </div>
			{/if}
			
			{* 登録 *}
			<form action="/expense/course/" method="POST" class="js-entry-form" style="display: none;">
				<input type="hidden" name="state" value="form">
			</form>
		</section>
	</main>
{/block}

{block name=footer_script}
	{include file="common/alert.tpl"}
{/block}