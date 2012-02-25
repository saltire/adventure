<?php
ob_start();
include "$approot/pages/fetch.php";
$json = ob_get_clean();
$output = json_decode($json, 1);

// show raw json output
//echo $json;

// show debug messages
//echo $output['debug'];
?>
<html>
	<head>
		<title><?php echo $output['title']; ?> &ndash; Adventure Engine</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="<?php echo $webroot; ?>/style.css" />
		<script type="text/javascript" src="<?php echo $webroot; ?>/js/jquery-1.4.2.min.js"></script>
		<script type="text/javascript" src="<?php echo $webroot; ?>/js/script.js"></script>
		<script type="text/javascript">
			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', 'UA-1741247-5']);
			_gaq.push(['_trackPageview']);
			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
		</script>
	</head>
	<body>
		<div id="main">
			<div id="output" class="border">
				<?php echo $output['message']; ?>
			</div>

			<form id="commandform" <?php echo ($output['status'] == 'ok') ? "" : 'class="hidden" '?>method="post" action="<?php echo WEB_ROOT; ?>/">
				<div class="prompt">&gt;</div>
				<input id="command" class="line" type="text" name="command" />
				<input type="hidden" name="action" value="command" />
				<button class="enter" type="submit">&crarr;</button>
			</form>

			<form id="continue" <?php echo ($output['status'] == 'paused') ? "" : 'class="hidden" '?>method="post" action="<?php echo WEB_ROOT; ?>/">
				<input type="hidden" name="action" value="continue" />
				<button class="border" type="submit">Continue...</button>
			</form>

			<form id="newgame" method="post" action="<?php echo $webroot; ?>/">
				<input type="hidden" name="action" value="newgame" />
				<button class="border" type="submit">New game</button>
			</form>

			<form id="help" method="get" action="<?php echo $webroot;?>/help">
				<button class="border">Help</button>
			</form>

			<img id="loader" src="loader.gif" alt="please wait..." />
		</div>
	</body>
</html>
