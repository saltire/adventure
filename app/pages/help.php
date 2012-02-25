<html>
	<head>
		<title>Help &ndash; Adventure Engine</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="<?php echo WEB_ROOT; ?>/style.css" />
		<script type="text/javascript" src="<?php echo WEB_ROOT; ?>/js/jquery-1.4.2.min.js"></script>
		<script type="text/javascript" src="<?php echo WEB_ROOT; ?>/js/script.js"></script>
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
			<div id="output">
				<p>
					Welcome to the wonderful world of text adventures.
					The Adventure Engine is designed to bring the experience of old-school,
					text-only adventure games to the web. If you never had the pleasure of
					playing these classic games, here's a quick primer.
				</p>
				<p>
					Each turn, I'll tell you where you are and what you can see.
					You give me a command, telling me what you want to do next.
					Generally, a command will consist of a verb and perhaps a noun.
					For example, you might want to <em>open the door</em>,
					<em>pull a lever</em>, or maybe <em>wear the invisibility cloak</em>.
				</p>
				<p>
					A command might also be a direction, such as <em>go north</em>.
					Often you can shorten a command like this to <em>north</em> or just <em>n</em>.
				</p>
				<p>
					Some other common commands you may want to use:
				</p>
				<ul>
					<li><em>Look around</em>, to get a description of your surroundings.</li>
					<li><em>Examine</em> an item, to take a closer look at it.</li>
					<li><em>Take inventory</em>, to see what you're carrying.</li>
				</ul>
				<p>
					These might also have short forms, like <em>x</em> for <em>examine</em>,
					<em>l</em> for <em>look</em>, or <em>inv</em> or <em>i</em> to check your inventory.
				</p>
				<p>
					The Adventure Engine also allows you to <em>save</em> your game at any given point,
					and <em>load</em> it again later. Adventuring can be dangerous business, after all.
				</p>
				<p>
					Feel free to get creative... you never know what you might be able to do. Happy adventuring!
				</p>
			</div>

			<form id="goback" method="get" action="<?php echo WEB_ROOT;?>/">
				<button class="border">OK, Let's Play!</button>
			</form>
		</div>
	</body>
</html>
