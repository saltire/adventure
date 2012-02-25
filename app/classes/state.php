<?php

class AdventureState {
	private $input = array();
	private $output = array();

	private $vars = array();
	private $rooms = array();
	private $nouns = array();

	private $current_room;
	private $queued_message = array();
	private $last_status;

	public function __construct($game) {
		session_start();

		if ($_SESSION['state']) {
			$this->setProperties($_SESSION['state']);

		} else {
			$this->resetState($game);
		}
	}

	public function __destruct() {
		$_SESSION['state'] = $this->getProperties();
	}

	public function saveStatus($status) {
		$this->last_status = $status;
	}

	public function resetState($game) {
		$this->input = array();
		$this->output = array();

		$this->vars = $game->getVarsInit();
		$this->rooms = $game->getRoomsInit();
		$this->nouns = $game->getNounsInit();

		$this->current_room = $game->getStartingRoom();
		$this->last_status = 'none';
		$this->queued_message = array();
	}

	public function saveGame() {
		$_SESSION['save'] = $this->getProperties();
	}

	public function loadGame() {
		if ($_SESSION['save']) {
			$this->setProperties($_SESSION['save']);
			return 1;

		} else {
			return 0;
		}
	}

	private function setProperties($source) {
		list(
				$this->input,
				$this->output,
				$this->vars,
				$this->rooms,
				$this->nouns,
				$this->current_room,
				$this->queued_message,
				$this->last_status
				) = $source;
	}

	private function getProperties() {
		return array(
				$this->input,
				$this->output,
				$this->vars,
				$this->rooms,
				$this->nouns,
				$this->current_room,
				$this->queued_message,
				$this->last_status
		);
	}

	public function logInput($input) {
		$i = count($this->input) + 1;
		$this->input[$i] = $input;
	}

	public function logOutput($output) {
		$this->output[] = $output;
	}

	public function getTurnNumber() {
		return count($this->input);
	}

	public function getCommandHistory() {
		return $this->input;
	}

	public function getMessageHistory() {
		return $this->output;
	}

	public function getLastStatus() {
		return $this->last_status;
	}

	public function queueMessage($message, $status) {
		$this->queued_message = array(
				'message' => $message,
				'status' => $status
		);
	}

	public function getQueuedMessage() {
		$queue = $this->queued_message;
		$this->queued_message = array();
		return $queue;
	}

	// room getters

	public function getRoomProperty($rid, $property) {
		return $this->rooms[$rid][$property];
	}

	public function getCurrentRoom() {
		return $this->current_room;
	}

	public function hasRoomBeenVisited($rid) {
		return array_key_exists('visited', $this->rooms[$rid]);
	}

	public function getNounsInRoom($rid) {
		$out = array();
		foreach ($this->nouns as $nid => $noun) {
			if (in_array($rid, $noun['loc'])) {
				$out[] = $nid;
			}
		}
		return $out;
	}

	// room setters

	public function setCurrentRoom($room) {
		$this->current_room = $room;
	}

	public function visitRoom($rid) {
		$this->rooms['visited'] = 1;
	}

	public function setRoomDesc($rid, $desc) {
		$this->rooms[$rid]['desc'] = $desc;
	}

	public function addRoomNote($rid, $note) {
		$this->rooms[$rid]['notes'][] = $note;
	}

	public function removeRoomNote($rid, $note) {
		$noteid = array_search($note, $this->rooms[$rid]['notes']);
		unset($this->rooms[$rid]['notes'][$noteid]);
	}

	public function clearRoomNotes($rid) {
		$this->rooms[$rid]['notes'] = array();
	}

	// noun getters

	public function getNounProperty($nid, $property) {
		return $this->nouns[$nid][$property];
	}

	public function doesNounExist($nid) {
		return array_key_exists($nid, $this->nouns) ? 1 : 0;
	}

	public function isNounSomewhere($nid) {
		return $this->nouns[$nid]['loc'] ? 1 : 0;
	}

	public function isNounInRoom($nid, $rid) {
		return in_array($rid, $this->nouns[$nid]['loc']) ? 1 : 0;
	}

	public function isNounInCurrentRoom($nid) {
		foreach ($this->getNounContainers($nid) as $container) {
			if ($this->isNounInCurrentRoom($container)) {
				return 1;
			}
		}
		return $this->isNounInRoom($nid, $this->current_room);
	}

	public function isNounInInventory($nid) {
		foreach ($this->getNounContainers($nid) as $container) {
			if ($this->isNounInInventory($container)) {
				return 1;
			}
		}
		return ($this->isNounInRoom($nid, 'INVENTORY')
						|| $this->isNounInRoom($nid, 'WORN'))
				? 1 : 0;
	}

	public function isNounWorn($nid) {
		return ($this->isNounInRoom($nid, 'WORN')) ? 1 : 0;
	}

	public function isNounPresent($nid) {
		return ($this->isNounInCurrentRoom($nid)
						|| $this->isNounInInventory($nid)
						|| $this->isNounWorn($nid))
				? 1 : 0;
	}

	public function getNounContents($nid) {
		$contents = array();
		foreach ($this->nouns as $cid => $content) {
			if (in_array("NOUN($nid)", $content['loc'])) {
				$contents[] = $cid;
			}
		}
		return $contents;
	}

	public function getNounContainers($nid) {
		$containers = array();
		foreach ($this->nouns[$nid]['loc'] as $loc) {
			if (preg_match('`NOUN\((.+)\)`', $loc, $matches)) {
				$containers[] = $matches[1];
			}
		}
		return $containers;
	}

	// noun setters

	public function addNounToRoom($nid, $room) {
		$this->nouns[$nid]['loc'] += is_array($room) ? $room : array($room);
	}

	public function sendNounToRoom($nid, $room) {
		$this->nouns[$nid]['loc'] = is_array($room) ? $room : array($room);
	}

	public function removeNounFromRoom($nid, $room) {
		foreach ((is_array($room) ? $room : array($room)) as $rid) {
			$roomid = array_search($rid, $this->nouns[$nid]['loc']);
			unset($this->nouns[$nid]['loc'][$roomid]);
		}
	}

	public function destroyNoun($nid) {
		$this->nouns[$nid]['loc'] = array();
	}

	public function swapNouns($nid1, $nid2) {
		$loc1 = $this->nouns[$nid1]['loc'];
		$loc2 = $this->nouns[$nid2]['loc'];
		$this->nouns[$nid1]['loc'] = $loc2;
		$this->nouns[$nid2]['loc'] = $loc1;
	}

	public function setNounDesc($nid, $newdesc) {
		$this->nouns[$nid]['desc'] = $newdesc;
	}

	public function addNounNote($nid, $mid) {
		$this->nouns[$nid]['notes'][] = $mid;
	}

	public function removeNounNote($nid, $mid) {
		$noteid = array_search($mid, $this->nouns[$nid]['notes']);
		unset($this->nouns[$nid]['notes'][$noteid]);
	}

	public function clearNounNotes($nid) {
		$this->nouns[$nid]['notes'] = array();
	}

	// variable getters

	public function getVar($vid) {
		if (is_array($vid)) {
			$out = array();
			foreach ($vid as $id) {
				$out[] = $this->vars[$id];
			}
			return $out;
		}
		return $this->vars[$vid];
	}

	// variable setters

	public function setVar($vid, $value) {
		$this->vars[$vid] = intval($value);
	}

	public function adjustVar($vid, $value) {
		$this->vars[$vid] = intval($this->vars[$vid]) + intval($value);
	}
}
