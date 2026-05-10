{extends file="admin/layout.tpl"}

{block name=content}
	<main class="u-mb-32">
		<section>
			<div class="heading u-mb-24">
				<h2 class="heading__title u-mb-16">ユーザー管理</h2>
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

			<form action="/expense/admin/user/edit/{$original_id}/" method="POST">

				{* 画面遷移 *}
				<input type="hidden" name="state" value="confirm">

				{* CSRFトークン *}
				<input type="hidden" name="csrf_token" value="{$csrf_token}">

				{* チェック用ユーザーID *}
				<input type="hidden" name="original_id" value="{$original_id}">

				<div class="form-block__group">

					{* ID *}
					<div class="form-block__field">
						<label class="form-block__label">ID</label>
						<div class="form-block__input-wrap">
							<input
								type="text"
								name="user_id"
								class="form-block__input {if isset($errors.user_id)}error-form{/if}"
								value="{$form_data.user_id}"
							>
							{if isset($errors.user_id)}
								<p class="error-text u-mt-8">{$errors.user_id}</p>
							{/if}
						</div>
					</div>

					{* 氏名 *}
					<div class="form-block__field">
						<label class="form-block__label">氏名</label>
						<div class="form-block__input-wrap">
							<input
								type="text"
								name="name"
								class="form-block__input {if isset($errors.name)}error-form{/if}"
								value="{$form_data.name}"
							>
							{if isset($errors.name)}
								<p class="error-text u-mt-8">{$errors.name}</p>
							{/if}
						</div>
					</div>

					{* メールアドレス *}
					<div class="form-block__field">
						<label class="form-block__label">メールアドレス</label>
						<div class="form-block__input-wrap">
							<input
								type="text"
								name="email"
								class="form-block__input {if isset($errors.email)}error-form{/if}"
								value="{$form_data.email}"
							>
							{if isset($errors.email)}
								<p class="error-text u-mt-8">{$errors.email}</p>
							{/if}
						</div>
					</div>

					{* パスワード *}
					<div class="form-block__field">
						<label for="password" class="form-block__label">パスワード</label>
						<div class="form-block__input-wrap">
							<div class="form-block__login login__control">
								<input
									type="password"
									name="password"
									value=""
									class="form-block__input {if isset($errors.password)}error-form{/if}"
								>
								<span class="login__toggle">
									<img
										src="{$BASE_URL}img/eye-open.svg"
										data-open="{$BASE_URL}img/eye-close.svg"
										data-close="{$BASE_URL}img/eye-open.svg"
										class="login__toggle-icon"
									>
								</span>
							</div>
							{if isset($errors.password)}
								<p class="error-text u-mt-8">{$errors.password}</p>
							{/if}
						</div>
					</div>

					{* 権限 *}
					<div class="form-block__field">
						<div class="form-block__label">権限</div>
						<div class="form-block__input-wrap">
							<label class="form-block__radio-wrap">
								<input
									type="radio"
									name="is_admin"
									value="0"
									class="form-block__radio"
									{if $form_data.is_admin == '0'}checked{/if}
								>一般
							</label>
							<label class="form-block__radio-wrap">
								<input
									type="radio"
									name="is_admin"
									value="1"
									class="form-block__radio"
									{if $form_data.is_admin == '1'}checked{/if}
								>管理者
							</label>
							{if isset($errors.is_admin)}
								<p class="error-text u-mt-8">{$errors.is_admin}</p>
							{/if}
						</div>
					</div>
				</div>

				{* ボタン群 *}
				<div class="button__wrap u-mt-24">
					<a href="/expense/admin/user/" class="button button--sub">戻る</a>
			    	<button type="submit" class="button button--main">確認画面へ</button>
			    </div>
			</form>
		</section>
	</main>
{/block}
