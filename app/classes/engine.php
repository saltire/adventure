<?php

class AdventureEngine {
	// this class is designed to execute one turn of an adventure game,
	// whose general data is stored in the AdventureGame class,
	// and whose current state is stored in the AdventureState class.

	// extend this class to add TESTS and ACTIONS in the form of methods
	// prepended with t_ and a_ respectively. these can be called from the
	// game's XML file.

	protected $game;
	protected $state;

	private $input = array();
	private $actions = array();
	private $status = 'ok';
	private $message = '';

	public function __construct($game) {
		$this->game = new AdventureGame($game);
		$this->state = new AdventureState($this->game);
	}

	public function getTitle() {
		return $this->game->getInfo('title');
	}

	public function startNewGame() {
		echo "start new game\n";
		$this->state->resetState($this->game);
		return $this->doTurn();
	}

	public function doTurn($input = '') {
		echo "do turn\n";

		if ($input) {
			// filter input
			$input = preg_replace('`[^\w\s]`', '', trim($input));
			$this->state->logInput($input);

			$input = preg_replace('`\b(the|a|an)\b`', '', $input);
			$input = preg_replace('`\s+`', ' ', $input);
			$this->input = array_slice(explode(' ', $input), 0, 4);

		} elseif ($this->state->getTurnNumber() > 0) {
			// if no input, show last turn's results
			return $this->getLastTurn();
		}

		// evaluate all conditional blocks for this input
		do {
			foreach ($this->game->getConds() as $condset) {
				$status = $this->doCondList($condset);
			}
		} while ($status == 'restart');

		// execute the action queue
		$this->doActions();

		// add default messages if necessary
		if (!$this->message) {
			$this->addMessageId('nothinghappens');
		}
		if ($this->status == 'gameover') {
			$this->addMessageId('gameover');
		}

		// replace wildcards in output
		$this->message = $this->insertInputWords($this->message);
		$search = array(
				'`%TURNS`e',
				'`%VAR\((.*?)\)`e'
		);
		$replace = array(
				'$this->state->getTurnNumber()',
				'$this->state->getVar(\'$1\')'
		);
		$this->message = preg_replace($search, $replace, $this->message);

		// log output and status, break up into segments if necessary
		$this->state->logOutput($this->message);
		$this->splitMessageQueue();
		$this->state->saveStatus($this->status);
		return array(
				'status' => $this->status,
				'message' => $this->message
		);
	}

	public function getLastTurn() {
		echo "get last turn\n";
		$laststatus = $this->state->getLastStatus();
		echo "last status: $laststatus\n";
		if ($laststatus == 'none') {
			return 0;
		}

		$this->status = $laststatus;
		$this->message = end($this->state->getMessageHistory());
		$this->splitMessageQueue();
		return array(
				'status' => $this->status,
				'message' => $this->message
		);
	}
	/*
	// experimental
	public function getHistory() {
		$laststatus = $this->state->getLastStatus();
		if ($laststatus == 'none') {
			return 0;
		}
		return array(
			'status' => $status,
			'commands' => $this->state->getCommandHistory(),
			'messages' => $this->state->getMessageHistory()
		);
	}
	*/
	public function getQueuedMessage() {
		echo "get queued message\n";
		$queued = $this->state->getQueuedMessage();
		if (!$queued) {
			return $this->getLastTurn();
		}
		$this->status = $queued['status'];
		$this->message = $queued['message'];

		$this->splitMessageQueue();
		$this->state->saveStatus($this->status);
		return array(
				'status' => $this->status,
				'message' => $this->message
		);
	}

	private function splitMessageQueue() {
		echo "split message queue\n";
		list($this->message, $queued) = explode('%PAUSE%', $this->message, 2);
		if ($queued) {
			echo "queueing message. deferring status: $this->status\n";
			$this->state->queueMessage($queued, $this->status);
			$this->status = 'paused';
		}
	}

	private function doCondList($condset) {
		// start going through conditions listed in game file
		foreach ($condset as $cond) {
			$status = $this->doCond($cond, $start);
			if ($status == 'gameover') {
				$this->status = 'gameover';
			}
			if ($status != 'ok') {
				break;
			}
		}
		return $status;
	}

	private function doCond($cond) {
		// evaluate all if statements in a cond, test to see if actions should be executed
		// if so, add them to the action queue and return status

		$match_cond = 0;
		//$debug = 1;

		// test if condition matches
		foreach ($cond['if'] as $if) {
			$match_tests = 1;
			echo $debug ? "testing $if: " : "";
			foreach (explode(',', $if) as $test) {

				if ($test == '*') {
					$match_tests = 1;

				} elseif ($test == 'start') {
					$match_tests = ($this->state->getTurnNumber() === 0) ? 1 : 0;

				} else {
					$neg = 0;
					if (substr($test, 0, 1) == '!') {
						$neg = 1;
						$test = substr($test, 1);
					}

					$words = explode(' ', $test);

					$testname = array_shift($words);
					$result = call_user_func_array(array($this, "t_$testname"), $words);
					$result = $neg ? ($result + 1) % 2 : $result;
					echo $debug ? "$result " : "";

					$match_tests = $result ? $match_tests : 0; // AND
				}

			}
			echo $debug ? "= $match_tests\n" : "";
			$match_cond = $match_tests ? 1 : $match_cond; // OR
		}

		return $match_cond ? $this->doThen($cond['then']) : 'ok';
	}

	private function doThen($then) {
		// evaluate all actions in a cond, and return status
		$status = 'ok';
		foreach ($then as $step) {
			switch($step['type']) {
				case 'cond':
					$status = $this->doCond($step['value']);
					if ($status != 'ok') {
						break 2;
					}
					break;

				case 'action':
					// add the action to the queue
					$this->actions[] = $step['value'];
					break;

				case 'replace':
					// clear the action queue and start over with the new input
					$this->input = explode(' ', $this->insertInputWords($step['value']));
					$this->actions = array();
					$status = 'restart';
					break 2;

				case 'done':
					// stop executing
					$status = 'done';
					break 2;

				case 'gameover':
					$status = 'gameover';
					break 2;
			}
		}
		return $status;
	}

	private function doActions() {
		// execute all actions in the queue
		foreach ($this->actions as $action) {
			//(debug) echo "action: $action... \n";
			$words = explode(' ', $action);
			$function = 'a_' . array_shift($words);
			call_user_func_array(array($this, $function), $words);
		}
	}


	// utility functions, to be used by tests and actions

	protected function addMessage($message) {
		$this->message .= $message;
	}

	protected function addMessageId($mid, $search = null, $replace = null) {
		$message = $this->game->getMessage($mid);

		if (isset($search) && isset($replace)) {
			$message = str_replace($search, $replace, $message);
		}
		$this->addMessage($message);
	}

	protected function insertInputWords($string) {
		// replaces numeric wildcards in a word or sentence with exact input words
		if (preg_match_all('`%(\d+)`', $string, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$string = str_replace($match[0], $this->input[$match[1] - 1], $string);
			}
		}
		return $string;
	}

	protected function getNounsFromString($string) {
		// replaces numeric wildcards in a word with nouns matching input words, returns array
		if (preg_match_all('`%(\d+)`', $string, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$string = str_replace($match[0], implode('|', $this->game->matchNouns($this->input[$match[1] - 1])), $string);
			}
		}
		return $string ? explode('|', $string) : array();
	}

	// sole test provided with engine

	protected function t_input() {
		$result = 1;
		foreach (func_get_args() as $w => $wstring) {
			$match_word = 0;
			foreach (explode('|', $wstring) as $word) {
				$match_word = (isset($this->input[$w])
								&& $this->game->matchWord($this->input[$w], $word))
						? 1 : $match_word; // OR
			}
			$result = $match_word ? $result : 0; // AND
		}
		return $result;
	}

}
