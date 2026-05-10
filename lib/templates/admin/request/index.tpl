{extends file="admin/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section>
			<div class="heading u-mb-24">
				<h2 class="heading__title u-mb-16">請求データ管理</h2>
				<p class="heading__text">
					期間選択の開始と終了と対象を選択後<br>
					「表示する」をクリックしてください
				</p>
			</div>

			{* 全体エラー *}
			{if $warning}
				{foreach from=$warning item=msg}
					<p class="error-text u-mb-8">{$msg}</p>
				{/foreach}
			{/if}

			{* 期間選択フォーム *}
			<form action="/expense/admin/" method="POST" class="history__filter u-mb-16">
				<div class="history__filter-inner">
					<div class="history__filter-field">
						<div class="history__filter-group">
							<span class="history__filter-label">期間選択</span>
							<select
								name="start_month"
								id="start_month"
								class="history__filter-select {if isset($errors.period)}error-form{/if}"
							>
								{foreach from=$start_month_options item=month}
									<option
										value="{$month.value}"
										{if $period_data.start_month == $month.value}selected{/if}
									>
										{$month.label}
									</option>
								{/foreach}
							</select>
							<span class="history__filter-delimiter">〜</span>
							<select
								name="end_month"
								id="end_month"
								class="history__filter-select {if isset($errors.period)}error-form{/if}"
							>
								{foreach from=$end_month_options item=month}
									{if $month.value > $period_data.start_month}
										<option
											value="{$month.value}"
											{if $period_data.end_month == $month.value}selected{/if}
										>
											{$month.label}
										</option>
									{/if}
								{/foreach}
							</select>
						</div>
						{if isset($errors.period)}
							<p class="error-text u-mt-12">{$errors.period}</p>
						{/if}
					</div>

					<div class="history__filter-field">
						<div class="history__filter-wrap">
							<div class="history__filter-group">
								<span class="history__filter-label">対象選択</span>
								<select
									name="user_id"
									class="history__filter-select {if isset($errors.user)}error-form{/if}"
								>
									<option value="" {if $selected_user_id == ''}selected{/if}>全員</option>
									{foreach from=$all_users item=user}
										<option
											value="{$user.id}"
											{if $selected_user_id == $user.id}selected{/if}
										>
											{$user.name}
										</option>
									{/foreach}
								</select>
							</div>
							<button type="submit" class="button button--main">表示する</button>
						</div>
						{if isset($errors.user)}
							<p class="error-text u-mt-12">{$errors.user}</p>
						{/if}
					</div>
				</div>
			</form>

			{* 対象期間表示 *}
			<div class="history__range u-mb-24">
				<span class="history__range-text">表示期間</span>
				<div class="history__range-group">
					<span class="history__range-start">{$period.from|default:''}</span>
					<span class="history__range-delimiter">〜</span>
					<span class="history__range-end">{$period.to|default:''}</span>
				</div>
			</div>

			{* 履歴テーブル *}
			<div class="table__wrap table__wrap--has-summary u-mb-24">
				<table class="table">
					<colgroup>
						<col style="width: auto;">
						<col style="width: auto;">
						<col style="width: auto;">
						<col style="width: auto;">
						<col style="width: auto;">
						<col style="width: auto;">
						<col style="width: auto;">
						<col style="width: auto;">
						<col style="width: auto;">
						<col style="width: auto;">
					</colgroup>
					<thead class="table__thead">
						<tr class="table__tr">
							<th class="table__th">請求日</th>
							<th class="table__th">購入日</th>
							<th class="table__th">請求者</th>
							<th class="table__th">路線</th>
							<th class="table__th">種別</th>
							<th class="table__th">区間</th>
							<th class="table__th">料金</th>
							<th class="table__th">訪問先</th>
							<th class="table__th">取消理由</th>
							<th class="table__th">操作</th>
						</tr>
					</thead>
					<tbody class="table__tbody">
						{if $requests.request_data|@count > 0}
							{foreach from=$requests.request_data item=user}

								{foreach from=$user.items item=row}
									{if $row.is_active == 1}
										<tr class="table__tr">
											<td class="table__td">{$row.created_at}</td>
											<td class="table__td">{$row.purchase_date}</td>
											<td class="table__td">{$row.user_name}</td>
											<td class="table__td">{$routes[$row.route_id]}</td>
											<td class="table__td">{$types[$row.type_id]}</td>
											<td class="table__td">{$row.section_from} 〜 {$row.section_to}</td>
											<td class="table__td table__td--fee">{$row.fee|number_format}円</td>
											<td class="table__td">{$row.note}</td>
											<td class="table__td table__td--dummy">ー</td>
											<td class="table__td table__td--link">
												<a href="/expense/admin/request/cancel/{$row.id}" class="table__link">取消</a>
											</td>
										</tr>
									{else}
										<tr class="table__tr table__tr--remove">
											<td class="table__td">{$row.created_at}</td>
											<td class="table__td">{$row.purchase_date}</td>
											<td class="table__td">{$row.user_name}</td>
											<td class="table__td">{$routes[$row.route_id]}</td>
											<td class="table__td">{$types[$row.type_id]}</td>
											<td class="table__td">{$row.section_from} 〜 {$row.section_to}</td>
											<td class="table__td table__td--fee">{$row.fee|number_format}円</td>
											<td class="table__td">{$row.note}</td>
											<td class="table__td">{$row.cancel_reason}</td>
											<td class="table__td table__td--dummy">ー</td>
										</tr>
									{/if}
								{/foreach}

								{* 氏名と合計 *}
								<tr class="table__tr table__tr--summary">
									<td class="table__td table__td--summary" colspan="10">
										<div class="table__summary">
											<p class="table__summary-person">{$user.user_name}</p>
											<p class="table__summary-total">
												合計<span class="table__total-price">{$user.sum_fee|number_format}円</span>
											</p>
										</div>
									</td>
								</tr>
							{/foreach}

							{* 総計 *}
							{if $is_all_selected && $requests.request_data|@count > 0}
								<tr class="table__tr table__tr--summary">
									<td class="table__td table__td--summary table__td--grand" colspan="10">
										<div class="table__summary table__summary--grand">
											<p class="table__summary-total">
												総計<span class="table__total-price">{$requests.total_fee|number_format}円</span>
											</p>
										</div>
									</td>
								</tr>
							{/if}
						{else}
							<tr class="table__tr">
								<td class="table__td table__td--nodata" colspan="10">表示対象のデータはありません</td>
							</tr>
						{/if}
					</tbody>
				</table>
			</div>

			{* CSVダウンロード *}
			<form action="/expense/admin/" method="POST" class="history__filter u-mb-16">
				<input type="hidden" name="state" value="download">
				<input type="hidden" name="csrf_token" value="{$csrf_token}">
				<input type="hidden" name="from" value="{$period.from_raw}">
				<input type="hidden" name="to" value="{$period.to_raw}">
				<input type="hidden" name="user_id" value="{$selected_user_id}">
				<div class="button__wrap">
				    <button type="submit" class="button button--main">CSVデータをダウンロードする</button>
				</div>
			</form>
		</section>
	</main>

	{* プルダウンの終了月を制限 *}
	<script type="text/javascript">
		const MONTH_OPTIONS = {$js_month_options|@json_encode nofilter};
	</script>
{/block}
