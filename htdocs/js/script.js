$(function() {

	// ==============================
	// 旅費請求：モーダルを開く
	// ==============================
	$(document).on('click', '.js-open-modal', function() {

		// 多重クリック防止
		if ($('#course-modal').is(':visible')) return;

		// ボタンがクリックされた行を取得
		const row = $(this).closest('.form-list__row');

		// モーダルのデータ属性に一時保存
		$('#course-modal').data('targetRow', row);

		// モーダルを開く
		$('#course-modal').fadeIn();
	});

	// ==============================
	// 旅費請求：よく使うコース自動入力
	// ==============================
	$(document).on('click', '.modal__item', function() {

		// 保存した対象行を取得
		const row = $('#course-modal').data('targetRow');

		// 対象行がなければ処理を中断
		if (! row) return;

		// data属性から値を取得
		const route	= $(this).data('route_id');
		const type 	= $(this).data('type_id');
		const from 	= $(this).data('section_from');
		const to   	= $(this).data('section_to');
		const fee  	= $(this).data('fee');
		const note 	= $(this).data('note');

		// 対象行に値をセット
		row.find('select[name="route[]"]').val(route);
		row.find('select[name="type[]"]').val(type);
		row.find('input[name="start[]"]').val(from);
		row.find('input[name="end[]"]').val(to);
		row.find('input[name="fee[]"]').val(fee);
		row.find('input[name="note[]"]').val(note);

		// モーダルを閉じる
		$('#course-modal').fadeOut();
	});

	// ==============================
	// 旅費請求：モーダルを閉じる
	// ==============================
	$(document).on('click', '.modal__overlay, .modal__close, .modal__item', function() {
		$('#course-modal').fadeOut();
	});

	// ==============================
	// 旅費請求：入力内容クリアボタン
	// ==============================
	$(document).on('click', '.js-clear-form', function(e) {

	    // フォーム送信を防止
	    e.preventDefault();

		// form要素を取得
		const $form = $(this).closest('form');

		// inputをクリア
		$form.find('input[type="text"]').val('');

		// selectをクリア
		$form.find('select').prop('selectedIndex', 0);

		// 最初の1行以外の要素を削除
		$form.find('.form-list__row:not(:first)').remove();
	});

	// ==============================
	// 旅費請求：料金入力で半角数字に変換
	// ==============================
	$(document).on('blur', '.form-list__input--number', function() {
		$(this).val((_, val) =>
			val.replace(/[０-９]/g, s => String.fromCharCode(s.charCodeAt(0) - 0xFEE0))
				.replace(/[^0-9]/g, '')
		);
	});

	// ==============================
	// 旅費請求：Datepicker
	// ==============================
	$.datepicker.setDefaults($.datepicker.regional['ja']);
	$('.js-datepicker').datepicker({
		dateFormat: 'yy/m/d'
	});

	// ==============================
	// 共通：新規登録とPOSTの連動
	// ==============================
	$(document).on('click', '.js-entry-button', function() {
		$('.js-entry-form').submit();
	});

	// ==============================
	// 共通：並び替えとPOSTの連動
	// ==============================
	$(document).on('click', '.js-sort-button', function() {
		$('.js-sort-form').submit();
	});

	// ==============================
	// 共通：削除アラート
	// ==============================
	$('.js-delete-button').on('click', function(e) {

		// チェックされたコース数をカウント
		const checked = $('.js-delete-check:checked').length;

		// チェックがない場合は処理中断
		if (checked == 0) {
			alert('削除対象を選択してください');

			// フォーム送信を防止
			e.preventDefault();
			return;
		}

		// 削除確認のポップアップ
		const proceed = confirm('本当に削除してよろしいですか？');

		// キャンセルされた場合は処理中断
		if (! proceed) {

			// フォーム送信を防止
			e.preventDefault();
			return;
		}
	});

	// ==============================
	// 共通：行の追加
	// ==============================
	$(document).on('click', '.form-list__add .button--add', function() {

		// 行の要素数をカウント
		const rowCount = $('.form-list__row').length + 1;

		// 最大10行で処理を中止
		if (rowCount > 10) {
			alert('これ以上は追加できません（最大10行）');
			return;
		}

		// 最初の行をクローン
		const $newRow = $('.form-list__row:first').clone();

		// 入力値をリセット
		$newRow.find('input, select').val('');

		// Datepickerの再初期化
		$newRow.find('.js-datepicker')
			.removeClass('hasDatepicker')
			.removeAttr('id')
			.datepicker({
				dateFormat: 'yy/m/d'
		});

		// バリデーションの赤枠とメッセージを削除
		$newRow.find('.error-form').removeClass('error-form');
		$newRow.find('.error-text').remove();

		// 作成した行を挿入
		$newRow.insertAfter('.form-list__row:last');

		// インデックスを更新
		$newRow.attr('data-index', rowCount);
		$newRow.find('.form-list__row-number').text(rowCount);
	});

	// ==============================
	// 共通：行の削除
	// ==============================
	$(document).on('click', '.form-list__action .button--remove', function() {

		// 行の要素を取得
		const $row = $(this).closest('.form-list__row');

		// 1行で処理を中止
		if ($('.form-list__row').length <= 1) {
			alert('1行目は削除できません');
			return;
		}

		// 入力済みチェック
		const hasInput = $row.find('input, select').filter(function () {
			return $(this).val().trim() !== '';
		}).length > 0;

		// 入力がある場合は削除確認のポップアップ
		if (hasInput) {
			const proceed = confirm('入力内容があります。本当に削除してよろしいですか？');

			// キャンセルされた場合は処理中断
			if (! proceed) {
				return;
			}
		}

		// 行を削除
		$row.remove();

		// インデックスを振り直し
		$('.form-list__row').each(function(i) {
			$(this).attr('data-index', i + 1);
			$(this).find('.form-list__row-number').text(i + 1);
		});
	});

	// ==============================
	// 共通：並び替え
	// ==============================

	// jQuery UIのsortableを初期化
	$('.sort').sortable({
		update: function () {

			// 並び順の変更でinputを更新
			updateSortIds();
		}
	});

	// 並び順をsort_ids[]で構築し直す
	function updateSortIds() {

		// 既存のhiddenのinputを削除
		$('.sort input[name="sort_ids[]"]').remove();

		$('.sort__list').each(function () {

			// data-idにメールIDを保持
			const id = $(this).data('id');

			const input = $('<input>').attr({
				type: 'hidden',
				name: 'sort_ids[]',
				value: id
			});

			$(this).append(input);
		});
	}

	// 念のためsubmit前にも更新
	$('.js-sort-form').on('submit', function () {
		updateSortIds();
	});

	// ==============================
	// 共通：並び替えを元に戻すボタン
	// ==============================

	// 初期順序を保持する配列
	const initialOrder = [];

	// ページ表示時に初期順序を保存
	$('.sort__list').each(function () {

		// data-idにメールIDを保持
		const id = $(this).data('id');

		// 配列にIDを格納
		initialOrder.push({
			id: id,
			element: $(this).clone(true, true)
		});
	});

	// 元に戻すボタンをクリックした際の処理
	$('.js-undo-button').on('click', function () {

		const $sortList = $('.sort');

		// 現在の並びをクリア
		$sortList.empty();

		// 初期順序に並び替えて再挿入
		initialOrder.forEach(item => {
			$sortList.append(item.element.clone(true, true));
		});

		// 既存のhiddenのinputも更新
		updateSortIds();
	});

	// ==============================
	// 履歴確認：プルダウンの終了月を制限
	// ==============================

	$('#start_month').on('change', function() {

		// プルダウンの選択月を取得
		const startVal = $(this).val();

		// 終了月のセレクトボックスを取得
		const $endSelect = $('#end_month');

		// 終了月のセレクトボックスを初期化
		$endSelect.empty();

		// 月配列から開始月より後の月を追加
		$.each(MONTH_OPTIONS, function(i, month) {

			if (month.value > startVal) {
				$endSelect.append(
					$('<option>', {
						value:	month.value,
						text:	month.label
					})
				);
			}
		});
	});

	// ==============================
	// ログイン画面：パスワード表示
	// ==============================
	$(document).on('click', '.login__toggle-icon', function() {

		// パスワード入力のテキストボックスを取得
		const $icon		= $(this);
		const $input	= $icon.closest('.login__control').find('input');

		// テキストボックスの属性を取得
		const isPassword = $input.attr('type') === 'password';

		// テキストボックスの属性を切り替える
		$input.attr('type', isPassword ? 'text' : 'password');

		// アイコン画像を切り替える
		$icon.attr('src', isPassword ? $icon.data('open') : $icon.data('close'));
	});
});
