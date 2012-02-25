$(document).ready(function() {
	$('input#command').focus();

	//$('#help').bind('submit', function() { 	});

	$('#commandform').bind('submit', function() {
		var command = $('#command').val();
		$('#loader').show();
		$.post('fetch', { action: 'command', command: command }, function(json) {
			$('img#loader').hide();
			var output = $.parseJSON(json);

			$('#output').append('<p>&gt; <span class="line">' + command + '</span></p>');
			$('#output').append(output.message);
			$('#output').animate({
				scrollTop: $('#output').attr('scrollHeight')
			}, 300);
			showFields(output.status);
		});
		return false;
	});

	$('#continue').bind('submit', function() {
		$('#loader').show();
		$.post('fetch', { action: 'continue' }, function(json) {
			$('#loader').hide();
			var output = $.parseJSON(json);

			$('#output').html(output.message);
			showFields(output.status);
		});
        return false;
	});

	$('#newgame').bind('submit', function() {
		$('#loader').show();
		$.post('fetch', { action: 'newgame' }, function(json) {
			$('#loader').hide();
			var output = $.parseJSON(json);

			$('#output').html(output.message);
			showFields(output.status);
		});
        return false;
	});
});

function showFields(status) {
	if (status == 'ok') {
		$('#commandform').show();
		$('#continue').hide();
		$('#command').val('').focus();
	}
	if (status == 'paused') {
		$('#commandform').hide();
		$('#continue').show();
	}
	if (status == 'gameover') {
		$('#commandform').hide();
		$('#continue').hide();
	}
}
