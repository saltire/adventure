<?php

class AdventureGame {
	private $rooms = array();
	private $nouns = array();
	private $words = array();
	private $messages = array();
	private $conds = array();

	private $info = array();
	private $config = array();
	private $vars = array();

	public function __construct($gamefile) {
		// TODO: add error checking, show a list of errors at the end

		$adv = simplexml_load_file($gamefile);

		foreach ($adv->info->children() as $id => $info) {
			$this->info["$id"] = "$info";
		}

		foreach ($adv->rooms->room as $room) {
			$rid = "{$room->attributes()->id}";

			if ($room->attributes()->start) {
				$this->config['start'] = $rid;
			}

			$notes = array();
			foreach ($room->note as $note) {
				$notes[] = "{$note->attributes()->id}";
			}

			$exits = array();
			foreach ($room->exit as $exit) {
				$exits["{$exit->attributes()->dir}"] = "{$exit->attributes()->room}";
			}

			$this->rooms[$rid] = array(
					'name' => "$room->name",
					'desc' => "$room->desc",
					'notes' => $notes,
					'exits' => $exits
			);
		}

		foreach ($adv->nouns->noun as $noun) {
			$locs = array();
			foreach ($noun->loc as $loc) {
				$locs[] = "{$loc->attributes()->id}";
			}

			$notes = array();
			foreach ($noun->note as $note) {
				$notes[] = "{$note->attributes()->id}";
			}

			$this->nouns["{$noun->attributes()->id}"] = array(
					'visible' => $noun->attributes()->visible ? 1 : 0,
					'movable' => $noun->attributes()->movable ? 1 : 0,
					'wearable' => $noun->attributes()->wearable ? 1 : 0,
					'name' => "$noun->name",
					'shortname' => "$noun->shortname",
					'desc' => "$noun->desc",
					'shortdesc' => "$noun->shortdesc",
					'notes' => $notes,
					'words' => $noun->words ? explode(',', $noun->words) : array(),
					'loc' => $locs
			);
			$this->addWordsToVocabulary($noun->words);
		}

		foreach ($adv->words->word as $words) {
			$this->addWordsToVocabulary($words);
		}

		foreach ($adv->vars->var as $var) {
			$this->vars["{$var->attributes()->id}"] = intval($var->attributes()->value);
		}

		foreach ($adv->messages->message as $message) {
			$this->messages["{$message->attributes()->id}"] = "{$message}";
		}

		if ($adv->conds->condset) {
			foreach ($adv->conds->condset as $condset) {
				$conds = array();
				foreach ($condset->cond as $cond) {
					$conds[] = $this->makeCondArray($cond);
				}
				$this->conds[] = $conds;
			}

		} else {
			$conds = array();
			foreach ($adv->conds->cond as $cond) {
				$conds[] = $this->makeCondArray($cond);
			}
			$this->conds[] = $conds;
		}
	}

	private function makeCondArray($cond) {
		$if = array();
		$then = array();

		foreach ($cond->children() as $type => $value) {
			if ($type == 'if') {
				$if[] = $this->trimValue($value);

			} else {
				$then[] = array(
					'type' => "$type",
					'value' => ("$type" == 'cond') ? $this->makeCondArray($value) : $this->trimValue($value)
				);
			}
		}

		return array(
				'if' => $if,
				'then' => $then
		);
	}

	private function trimValue($value) {
		return preg_replace('`\s+`', ' ', trim($value));
	}

	private function addWordsToVocabulary($words) {
		$words = explode(',', $words);
		foreach ($words as $word) {
			if (!array_key_exists($word, $this->words)) {
				$this->words[$word] = array();
			}
			$this->words[$word] = array_unique(array_merge($this->words[$word], $words));
		}
	}

	// getters

	public function getInfo($id) {
		return $this->info[$id];
	}

	public function getConds() {
		return $this->conds;
	}

	public function getMessage($mid) {
		return $this->messages[$mid];
	}

	// state inits

	public function getStartingRoom() {
		return $this->config['start'];
	}

	public function getRoomsInit() {
		$rooms = array();
		foreach ($this->rooms as $rid => $room) {
			$rooms[$rid] = $room;
		}
		return $rooms;
	}

	public function getNounsInit() {
		$nouns = array();
		foreach ($this->nouns as $nid => $noun) {
			$nouns[$nid] = $noun;
		}
		return $nouns;
	}

	public function getVarsInit() {
		return $this->vars;
	}

	// word matching

	public function doesWordExist($word) {
		return array_key_exists($word, $this->words);
	}

	public function matchWord($iword, $word) {
		if ($word == "*" || (array_key_exists($word, $this->words) && in_array($iword, $this->words[$word]))) {
			return 1;
		}
		return 0;
	}

	public function matchNouns($iword) {
		$nouns = array();
		foreach ($this->nouns as $nid => $noun) {
			if (in_array($iword, $noun['words'])) {
				$nouns[] = $nid;
			}
		}
		return $nouns;
	}

}
