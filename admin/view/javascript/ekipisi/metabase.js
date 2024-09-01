$(document).ready(function () {
	$('.selectall:button').click(function () {
		var d = $(this).data();
		$('.' + d.container + ' :checkbox').prop('checked', !d.checked);
		d.checked = !d.checked;
		$(this).html(d.checked ? d.deselect : d.select);
	});

	$('.btn-copy').click(function (e) {
		var key_id = $(this).data('id');
		var aux = document.createElement('input');
		aux.setAttribute(
			'value',
			document.getElementById('input-feed-url-' + key_id).value
		);
		document.body.appendChild(aux);
		aux.select();
		document.execCommand('copy');
		document.body.removeChild(aux);
		$.notify($(this).data('text'), 'success');
	});
});
