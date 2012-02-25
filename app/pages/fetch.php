<?php

// this page returns JSON format so it can be parsed by either PHP or Javascript
// that means debug messages in the adventure object won't show up directly on screen
// instead, they are saved to the 'debug' variable in the json string

ob_start(); // catch debug messages

$adv = new AdventureEngineBase('starflight');

// execute command
if ($_POST['action'] == 'command') {
	$output = $adv->doTurn($_POST['command']);
	$message = $output['message'];

// get the next part of a queued message
} elseif ($_POST['action'] == 'continue') {
	$output = $adv->getQueuedMessage();
	$message = $output['message'];

// start a new game
} elseif ($_POST['action'] == 'newgame') {
	$output = $adv->startNewGame();
	$message = $output['message'];

// default
} else {
	$output = $adv->getLastTurn();
	if ($output) {
		$message = $output['message'];

	} else {
		$output = $adv->startNewGame();
		$message = $output['message'];
	}

	/*
	$output = $adv->getHistory();
	if ($output) {
		$message = '';
		foreach ($output['messages'] as $t => $msg) {
			$message .= $t > 0 ? "&gt;<span class=\"line\">{$output['commands'][$t]}</span>\n\n$msg\n\n" : "$msg\n\n";
		}
		$message = end(explode('%PAUSE%', $message));
		
	} else {
		$output = $adv->startNewGame();
		$message = $output['message'];
	}
	 */
}

$debug = ob_get_clean();

$json = array(
	'title' => $adv->getTitle(),
	'status' => $output['status'],
	'message' => '',
	'debug' => $debug
);
foreach (explode('\n\n', $message) as $para) {
	$json['message'] .= "<p>" . str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', nl2br($para)) . "</p>\n";
}

echo json_encode($json);
