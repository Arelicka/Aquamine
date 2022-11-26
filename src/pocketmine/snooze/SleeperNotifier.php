<?php

declare(strict_types=1);

namespace pocketmine\snooze;

use function assert;

/**
 * Notifiers are Threaded objects which can be attached to threaded sleepers in order to wake them up.
 */
class SleeperNotifier extends \Threaded{

	private \Threaded $sharedObject;

	private int $sleeperId;

	final public function attachSleeper(\Threaded $sharedObject, int $id) : void{
		$this->sharedObject = $sharedObject;
		$this->sleeperId = $id;
	}

	final public function getSleeperId() : int{
		return $this->sleeperId;
	}

	/**
	 * Call this method from other threads to wake up the main server thread.
	 */
	final public function wakeupSleeper() : void{
		$shared = $this->sharedObject;
		assert($shared !== null);
		$sleeperId = $this->sleeperId;
		$shared->synchronized(function() use ($shared, $sleeperId) : void{
			if(!isset($shared[$sleeperId])){
				$shared[$sleeperId] = $sleeperId;
				$shared->notify();
			}
		});
	}
}
