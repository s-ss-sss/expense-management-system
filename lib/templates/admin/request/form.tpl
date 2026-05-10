{extends file="admin/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section>
			<div class="heading u-mb-24">
				<h2 class="heading__title u-mb-16">内容確認</h2>
				<p class="heading__text">
					こちらの内容を取消してよろしいですか？<br>
					問題がなければ「取消理由」を入力後<br>
					「実行する」をクリックしてください
				</p>
			</div>
			
			{* 全体エラー *}
			{if $warning}
				{foreach from=$warning item=msg}
					<p class="error-text u-mb-8">{$msg}</p>
				{/foreach}
			{/if}
			
			<form action="/expense/admin/request/cancel/{$request.id}/" method="POST">
				
				{* 画面遷移 *}
				<input type="hidden" name="state" value="complete">
				
				{* CSRFトークン *}
				<input type="hidden" name="csrf_token" value="{$csrf_token}">
				
				{* 請求データID *}
				<input type="hidden" name="request_id" value="{$request.id}">
				
				<dl class="form-block__group u-mb-12">
					<div class="form-block__field">
						<dt class="form-block__label">請求日</dt>
						<dd class="form-block__input-wrap">
							{$request.created_at}
							<input type="hidden" name="created_at" value="{$request.created_at}">
						</dd>
					</div>
					<div class="form-block__field">
						<dt class="form-block__label">購入日</dt>
						<dd class="form-block__input-wrap">
							{$request.purchase_date}
							<input type="hidden" name="purchase_date" value="{$request.purchase_date}">
						</dd>
					</div>
					<div class="form-block__field">
						<dt class="form-block__label">請求者</dt>
						<dd class="form-block__input-wrap">
							{$request.user_name}
							<input type="hidden" name="user_name" value="{$request.user_name}">
						</dd>
					</div>
					<div class="form-block__field">
						<dt class="form-block__label">路線</dt>
						<dd class="form-block__input-wrap">
							{$routes[$request.route]}
							<input type="hidden" name="route" value="{$request.route}">
						</dd>
					</div>
					<div class="form-block__field">
						<dt class="form-block__label">種別</dt>
						<dd class="form-block__input-wrap">
							{$types[$request.type]}
							<input type="hidden" name="type" value="{$request.type}">
						</dd>
					</div>
					<div class="form-block__field">
						<dt class="form-block__label">区間</dt>
						<dd class="form-block__input-wrap">
							{$request.start} 〜 {$request.end}
							<input type="hidden" name="start" value="{$request.start}">
							<input type="hidden" name="end" value="{$request.end}">
						</dd>
					</div>
					<div class="form-block__field">
						<dt class="form-block__label">料金</dt>
						<dd class="form-block__input-wrap">
							{$request.fee|number_format}円
							<input type="hidden" name="fee" value="{$request.fee}">
						</dd>
					</div>
					<div class="form-block__field">
						<dt class="form-block__label">訪問先</dt>
						<dd class="form-block__input-wrap">
							{$request.note}
							<input type="hidden" name="note" value="{$request.note}">
						</dd>
					</div>
				</dl>
				
				{* 取消理由 *}
				<div class="form-block__group">
					<div class="form-block__field">
						<label for="route" class="form-block__label form-block__label--accent">取消理由</label>
						<div class="form-block__textarea-wrap">
							<textarea
								name="cancel_reason"
								class="form-block__textarea {if isset($errors.cancel_reason)}error-form{/if}"
								rows="3"
								placeholder="取消理由を簡潔に入力してください（例：申請ミスなど）"
							>{$request.cancel_reason}</textarea>
						</div>
					</div>
				</div>
				
				{* エラー *}
				{if isset($errors.cancel_reason)}
					<p class="error-text u-mt-8">{$errors.cancel_reason}</p>
				{/if}
				
				{* ボタン群 *}
				<div class="button__wrap u-mt-24">
					<a href="/expense/admin/request/" class="button button--sub">戻る</a>
			    	<button type="submit" class="button button--main">実行する</button>
			    </div>
			</form>
		</section>
	</main>
{/block}