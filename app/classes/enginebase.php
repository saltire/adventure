<?php

class AdventureEngineBase extends AdventureEngine {

	// TESTS

	// player tests

	protected function t_room($rooms) {
		$result = 0;
		foreach (explode('|', $rooms) as $rid) {
			$result = ($this->state->getCurrentRoom() == $rid) ? 1 : $result; // OR
		}
		return $result;
	}

	protected function t_carryingsomething() {
		return $this->state->getNounsInRoom('INVENTORY') || $this->state->getNounsInRoom('WORN') ? 1 : 0;
	}

	protected function t_wearingsomething() {
		return $this->state->getNounsInRoom('WORN') ? 1 : 0;
	}

	// room tests

	protected function t_exitexists($dir) {
		$inputdir = $this->insertInputWords($dir);
		foreach ($this->state->getRoomProperty($this->state->getCurrentRoom(), 'exits') as $edir => $exit) {
			if ($this->game->matchWord($inputdir, $edir)) {
				return 1;
			}
		}
		return 0;
	}

	// noun tests

	protected function t_nounloc($noun, $room) {
		$result = 0;
		foreach ($this->getNounsFromString($noun) as $nid) {
			if ($this->state->doesNounExist($nid)) {
				foreach (explode('|', $room) as $rid) {
					$result = $this->state->isNounInRoom($nid, $rid) ? 1 : $result; // OR
				}
			}
		}
		return $result;
	}

	protected function t_present($noun) {
		$result = 0;
		foreach ($this->getNounsFromString($noun) as $nid) {
			$result = $this->state->isNounPresent($nid) ? 1 : $result; // OR
		}
		return $result;
	}

	protected function t_inroom($noun) {
		$result = 0;
		foreach ($this->getNounsFromString($noun) as $nid) {
			$result = $this->state->isNounInCurrentRoom($nid) ? 1 : $result; // OR
		}
		return $result;
	}

	protected function t_ininv($noun) {
		$result = 0;
		foreach ($this->getNounsFromString($noun) as $nid) {
			$result = $this->state->isNounInInventory($nid) ? 1 : $result; // OR
		}
		return $result;
	}

	protected function t_worn($noun) {
		$result = 0;
		foreach ($this->getNounsFromString($noun) as $nid) {
			$result = $this->state->isNounWorn($nid) ? 1 : $result; // OR
		}
		return $result;
	}

	protected function t_wearable($noun) {
		$result = 0;
		foreach ($this->getNounsFromString($noun) as $nid) {
			$result = $this->state->getNounProperty($nid, 'wearable') ? 1 : $result; // OR
		}
		return $result;
	}

	protected function t_hascontents($noun) {
		$result = 0;
		foreach ($this->getNounsFromString($noun) as $nid) {
			$result = $this->state->getNounContents($nid) ? 1 : $result; // OR
		}
		return $result;
	}

	protected function t_contained($noun) {
		$result = 0;
		foreach ($this->getNounsFromString($noun) as $nid) {
			$result = $this->state->getNounContainers($nid) ? 1 : $result; // OR
		}
		return $result;
	}

	protected function t_somewhere($noun) {
		$result = 0;
		foreach ($this->getNounsFromString($noun) as $nid) {
			$result = $this->state->isNounSomewhere($nid) ? 1 : $result; // OR
		}
		return $result;
	}

	protected function t_together($noun1, $noun2) {
		$result = 0;
		foreach ($this->getNounsFromString($noun1) as $nid1) {
			foreach ($this->getNounsFromString($noun2) as $nid2) {
				$result = $this->state->isNounInRoom($nid1, $this->state->getNounProperty($nid2, 'loc')) ? 1 : $result; // OR
			}
		}
		return $result;
	}

	// numerical tests

	protected function t_random($num) {
		return (mt_rand(1, 100) > intval($num)) ? 1 : 0;
	}

	protected function t_var($vid, $value) {
		$var = $this->state->getVar($vid);
		$result = 0;

		if (preg_match('`([<|=|>]*)(-?\d+)`', $value, $matches)) {
			$op = $matches[1];
			$value = intval($matches[2]);

			if ($op == "") {
				$result = ($var == $value) ? 1 : $result;

			} else {
				$result = ((strpos($op, '<') !== false) && ($var < $value)) ? 1 : $result; // OR
				$result = ((strpos($op, '=') !== false) && ($var == $value)) ? 1 : $result; // OR
				$result = ((strpos($op, '>') !== false) && ($var > $value)) ? 1 : $result; // OR
			}
		}
		return $result;
		//return $this->evaluate($this->state->getVar($vid), $op, intval($value));
	}

	protected function t_turns($op, $value) {
		return $this->evaluate($this->state->getTurnNumber(), $op, intval($value));
	}

	protected function evaluate($v1, $op, $v2) {
		if ((in_array($op, array('gt', 'greaterthan', '>')) && $v1 > $v2)
				|| (in_array($op, array('lt', 'lessthan', '<')) && $v1 < $v2)
				|| (in_array($op, array('eq', 'equals', '=', '==')) && $v1 == $v2)
				|| (in_array($op, array('=>', '>=')) && $v1 >= $v2)
				|| (in_array($op, array('=<', '<=')) && $v1 <= $v2)) {
			return 1;
		}
		return 0;
	}

	// ACTIONS

	// game-related actions

	protected function a_save() {
		$this->state->saveGame();
		$this->addMessageId('saved');
	}

	protected function a_load() {
		if ($this->state->loadGame()) {
			$this->addMessageId('loadedsave');
			$this->a_look('', 0);

		} else {
			$this->addMessageId('nosave');
		}
	}

	protected function a_message($mid) {
		$this->addMessageId($mid);
	}

	protected function a_pause() {
		$this->addMessage('%PAUSE%');
	}

	// player actions

	protected function a_move($inputdir) {
		$exits = $this->state->getRoomProperty($this->state->getCurrentRoom(), 'exits');
		$match_exit = 0;
		foreach ($exits as $exitdir => $room) {
			if ($this->game->matchWord($exitdir, $this->insertInputWords($inputdir))) {
				$match_exit = 1;
				$this->state->setCurrentRoom($room);
				$this->a_look(0);
			}
		}
		if (!$match_exit) {
			$this->addMessageId('cantgo');
		}
	}

	protected function a_take($noun) {
		$movable = 0;
		$present = 0;
		$notcarrying = 0;
		foreach ($this->getNounsFromString($noun) as $nid) {
			if ($this->state->getNounProperty($nid, 'movable')) {
				$movable = 1;
				if ($this->state->isNounPresent($nid)) {
					$present = 1;
					if (!$this->state->isNounInInventory($nid)) {
						$notcarrying = 1;
						$this->state->sendNounToRoom($nid, 'INVENTORY');
						$this->addMessageId('taken');
					}
				}
			}
		}
		if (!$movable) {
			$this->addMessageId('cantverb');
		} elseif (!$present) {
			$this->addMessageId('dontsee');
		} elseif (!$notcarrying) {
			$this->addMessageId('alreadycarrying');
		}
	}

	protected function a_drop($noun) {
		$carrying = 0;
		foreach ($this->getNounsFromString($noun) as $nid) {
			if ($this->state->isNounInInventory($nid)) {
				$carrying = 1;
				$this->state->sendNounToRoom($nid, $this->state->getCurrentRoom());
				$this->addMessageId('dropped');
			}
		}
		if (!$carrying) {
			$this->addMessageId('donthave');
		}
	}

	protected function a_wear($noun) {
		$wearable = 0;
		$present = 0;
		$notworn = 0;
		foreach ($this->getNounsFromString($noun) as $nid) {
			if ($this->state->getNounProperty($nid, 'wearable')) {
				$wearable = 1;
				if ($this->state->isNounPresent($nid)) {
					$present = 1;
					if (!$this->state->isNounWorn($nid)) {
						$notworn = 1;
						$this->state->sendNounToRoom($nid, 'WORN');
						$this->addMessageId('wearing');
					}
				}
			}
		}
		if (!$wearable) {
			$this->addMessageId('cantverb');
		} elseif (!$present) {
			$this->addMessageId('dontsee');
		} elseif (!$notworn) {
			$this->addMessageId('alreadywearing');
		}
	}

	protected function a_inv() {
		$inv = array();
		foreach ($this->state->getNounsInRoom('WORN') as $nid) {
			$inv[$nid] = 1;
		}
		foreach ($this->state->getNounsInRoom('INVENTORY') as $nid) {
			$inv[$nid] = 0;
		}

		$carrying = 0;
		foreach ($inv as $nid => $worn) {
			if (!$carrying) {
				$this->addMessageId('carrying');
				$carrying = 1;
			}

			$this->addMessageId(($worn ? 'invitemworn' : 'invitem'), '%NOUN', $this->state->getNounProperty($nid, 'name'));
			$this->showNounContents($nid);
		}

		if (!$inv) {
			$this->addMessageId('carryingnothing');
		}
	}

	protected function showNounContents($nid) {
		$contents = $this->state->getNounContents($nid);
		if ($contents) {
			$this->addMessageId('invitemcontains', '%NOUN', $this->state->getNounProperty($nid, 'shortname'));
			foreach ($contents as $content) {
				$this->addMessageId('invitemcontained', '%NOUN', $this->state->getNounProperty($content, 'name'));
				$this->showNounContents($content, $level + 1);
			}
		}
	}

	// room descriptions

	protected function a_look($full = 1) {
		$rid = $this->state->getCurrentRoom();
		if (!$this->state->hasRoomBeenVisited($rid) || $full) {
			$this->state->visitRoom($rid);

			$this->addMessage($this->state->getRoomProperty($rid, 'desc'));
			foreach ($this->state->getRoomProperty($rid, 'notes') as $note) {
				$this->addMessageId($note);
			}

			$t = 0;
			foreach ($this->state->getNounsInRoom($rid) as $nid) {
				if ($this->state->getNounProperty($nid, 'visible')) {
					if (!$t) {
						$t = 1;
						$this->addMessage("\n");
					}
					$this->addMessage("\n" . $this->state->getNounProperty($nid, 'shortdesc'));
					$this->showNounContents($nid);
				}
			}

		} else {
			$this->addMessage($this->state->getRoomProperty($rid, 'name') . ".");
		}
	}

	protected function a_setroomdesc($room, $mid) {
		foreach (explode(',', $room) as $rid) {
			$this->state->setRoomDesc($rid, $this->game->getMessage($mid));
		}
	}

	protected function a_addroomnote($room, $msg) {
		foreach (explode(',', $room) as $rid) {
			foreach (explode(',', $msg) as $mid) {
				$this->state->addRoomNote($rid, $mid);
			}
		}
	}

	protected function a_removeroomnote($room, $msg) {
		foreach (explode(',', $room) as $rid) {
			foreach (explode(',', $msg) as $mid) {
				$this->state->removeRoomNote($rid, $mid);
			}
		}
	}

	protected function a_clearroomnotes($room) {
		foreach (explode(',', $room) as $rid) {
			$this->state->clearRoomNotes($rid);
		}
	}

	// noun descriptions

	protected function a_examine($noun) {
		$desc = 0;
		foreach ($this->getNounsFromString($noun) as $nid) {
			if ($this->state->isNounPresent($nid)) {
				if ($this->state->getNounProperty($nid, 'desc') || $this->state->getNounProperty($nid, 'notes')) {
					$desc = 1;
					$this->addMessage($this->state->getNounProperty($nid, 'desc'));
					foreach ($this->state->getNounProperty($nid, 'notes') as $note) {
						$this->addMessageId($note);
					}
				}
				if ($this->showNounContents($nid)) {
					$desc = 1;
				}
			}
		}
		if (!$desc) {
			$this->addMessageId('nothingunusual');
		}
	}

	protected function a_showcontents($noun) {
		foreach ($this->getNounsFromString($noun) as $nid) {
			$this->showNounContents($nid);
		}
	}

	protected function a_setnoundesc($noun, $mid) {
		foreach ($this->getNounsFromString($noun) as $nid) {
			$this->state->setNounDesc($nid, $this->game->getMessage($mid));
		}
	}

	protected function a_addnounnote($noun, $msg) {
		foreach ($this->getNounsFromString($noun) as $nid) {
			foreach (explode(',', $msg) as $mid) {
				$this->state->addNounNote($nid, $mid);
			}
		}
	}

	protected function a_removenounnote($noun, $msg) {
		foreach ($this->getNounsFromString($noun) as $nid) {
			foreach (explode(',', $msg) as $mid) {
				$this->state->removeNounNote($nid, $mid);
			}
		}
	}

	protected function a_clearnounnotes($noun) {
		foreach ($this->getNounsFromString($noun) as $nid) {
			$this->state->clearNounNotes($noun);
		}
	}

	// adding a noun

	protected function a_addnoun($noun, $room) {
		foreach ($this->getNounsFromString($noun) as $nid) {
			foreach (explode(',', $room) as $rid) {
				$this->state->addNounToRoom($nid, $rid);
			}
		}
	}

	protected function a_addtoroom($noun) {
		$this->a_addnoun($noun, $this->state->getCurrentRoom());
	}

	protected function a_addtonoun($noun, $targets) {
		foreach (explode(',', $noun2) as $target) {
			$targets[] = "NOUN($target)";
		}
		foreach ($this->getNounsFromString($noun1) as $nid) {
			$this->state->addNounToRoom($nid, $targets);
		}
	}

	protected function a_addtonounloc($noun, $target) {
		$this->a_addnoun($noun, $this->state->getNounProperty($target, 'loc'));
	}

	// sending a noun

	protected function a_sendnoun($noun, $room) {
		foreach ($this->getNounsFromString($noun) as $nid) {
			$this->state->sendNounToRoom($nid, explode(',', $room));
		}
	}

	protected function a_sendtoroom($noun) {
		$this->a_sendnoun($noun, $this->state->getCurrentRoom());
	}

	protected function a_sendtonoun($noun1, $noun2) {
		foreach (explode(',', $noun2) as $target) {
			$targets[] = "NOUN($target)";
		}
		foreach ($this->getNounsFromString($noun1) as $nid) {
			$this->state->sendNounToRoom($nid, $targets);
		}
	}

	protected function a_sendtonounloc($noun, $target) {
		$this->a_sendnoun($noun, $this->state->getNounProperty($target, 'loc'));
	}

	// other noun actions

	protected function a_removenoun($noun, $room) {
		foreach ($this->getNounsFromString($noun) as $nid) {
			$this->state->removeNounFromRoom($nid, explode(',', $room));
		}
	}

	protected function a_destroy($noun) {
		foreach ($this->getNounsFromString($noun) as $nid) {
			$this->state->destroyNoun($nid);
		}
	}

	protected function a_sendallinroom($room1, $room2) {
		foreach (explode(',', $room1) as $rid) {
			foreach ($this->state->getNounsInRoom($rid) as $nid) {
				$this->state->sendNounToRoom($nid, explode(',', $room2));
			}
		}
	}

	protected function a_removeallinroom($room) {
		foreach (explode(',', $room) as $rid) {
			foreach ($this->state->getNounsInRoom($rid) as $nid) {
				$this->state->removeNounFromRoom($nid, $rid);
			}
		}
	}

	protected function a_destroyallinroom($room) {
		foreach (explode(',', $room) as $rid) {
			foreach ($this->state->getNounsInRoom($rid) as $nid) {
				$this->state->destroyNoun($nid);
			}
		}
	}

	protected function a_swapnouns($noun1, $noun2) {
		$nid1 = array_shift(explode(',', $noun1));
		$nid2 = array_shift(explode(',', $noun2));
		$this->state->swapNouns($nid1, $nid2);
	}

	// variables

	protected function a_setvar($vid, $value) {
		$this->state->setVar($vid, $value);
	}

	protected function a_adjustvar($vid, $value) {
		$this->state->adjustVar($vid, $value);
	}

}
