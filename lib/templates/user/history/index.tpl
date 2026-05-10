{extends file="user/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section class="history">
			<div class="heading u-mb-24">
				<h2 class="heading__title u-mb-16">履歴確認</h2>
				<p class="heading__text">
					期間選択の開始と終了を選択後<br>
					「表示する」をクリックしてください
				</p>
			</div>
			
			{* 期間選択フォーム *}
			<form action="/expense/history/" method="POST" class="history__filter u-mb-16">
				<input type="hidden" name="mode" value="history">
				<div class="history__filter-inner">
					<div class="history__filter-field">
						<div class="history__filter-wrap">
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
							<button type="submit" class="button button--main">表示する</button>
						</div>
						{if isset($errors['period'])}
							<p class="error-text u-mt-12">{$errors.period}</p>
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
			<div class="table__wrap table__wrap--has-summary">
				<table class="table">
					<colgroup>
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
							<th class="table__th">路線</th>
							<th class="table__th">種別</th>
							<th class="table__th">区間</th>
							<th class="table__th">料金</th>
							<th class="table__th">訪問先</th>
						</tr>
					</thead>
					<tbody class="table__tbody">
						
						{if $history.history_data|@count > 0}
							{foreach from=$history.history_data item=row}
								<tr class="table__tr">
									<td class="table__td">{$row.created_at}</td>
									<td class="table__td">{$row.purchase_date}</td>
									<td class="table__td">{$routes[$row.route_id]}</td>
									<td class="table__td">{$types[$row.type_id]}</td>
									<td class="table__td">{$row.section_from} 〜 {$row.section_to}</td>
									<td class="table__td table__td--fee">{$row.fee|number_format}円</td>
									<td class="table__td">{$row.note}</td>
								</tr>
							{/foreach}
						{else}
							<tr class="table__tr">
								<td class="table__td table__td--nodata" colspan="7">表示対象のデータはありません</td>
							</tr>
						{/if}
						
						{* 件数と合計 *}
						<tr class="table__tr table__tr--summary">
							<td class="table__td table__td--summary" colspan="9">
								<div class="table__summary">
									<p class="table__summary-count u-mr-8">
										全<span class="table__count-number">{$history.total_count|number_format}</span>件
									</p>
									<p class="table__summary-total">
										合計<span class="table__total-price">{$history.total_fee|number_format}円</span>
									</p>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</section>
	</main>
	
	{* プルダウンの終了月を制限 *}
	<script type="text/javascript">
		const MONTH_OPTIONS = {$js_month_options|@json_encode nofilter};
	</script>
{/block}