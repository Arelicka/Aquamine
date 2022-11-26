<?php

declare(strict_types=1);

interface LoggerAttachment{

	/**
	 * @param mixed  $level
	 * @param string $message
	 */
	public function log($level, $message);

}