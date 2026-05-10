{if $redirect_deleted}
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			setTimeout(function() {
				alert('削除が完了しました');
			}, 100);
		});
	</script>
{/if}