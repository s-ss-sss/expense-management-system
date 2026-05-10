{foreach from=$form_data.date key=num item=date}
No.{$num + 1}
購入日：{$form_data.date[$num]}
路線　：{$routes[$form_data.route[$num]]}
種別　：{$types[$form_data.type[$num]]}
区間　：{$form_data.start[$num]} 〜 {$form_data.end[$num]}
料金　：{$form_data.fee[$num]|number_format}円
訪問先：{$form_data.note[$num]}

{/foreach}