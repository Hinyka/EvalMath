<?php

namespace Hinyka\EvalMath;

use Exception;

/**
 * Class EvalMathException
 *
 * @package Hinyka\EvalMath
 * @author Karel Hink
 * @license BSD-3-Clause
 * @link https://github.com/Hinyka/EvalMath
 * @version 2.0.0
 */
class EvalMathException extends Exception {
	/**
	 * @var int
	 */
	private static int $logLevel = 1;

	/**
	 * Set the log level (0: none, 1: standard, 2: detailed)
	 *
	 * @param int $level
	 */
	public static function setLogLevel(int $level): void {
		self::$logLevel = $level;
	}

	/**
	 * @param string $msg
	 */
	public function __construct(string $msg) {
		parent::__construct($msg);
		$this->log();
	}

	/**
	 * Log the error message
	 */
	private function log(): void {
		$msg = "";

		if (self::$logLevel >= 1) {
			$msg .= date("Y-m-d H:i:s") . " [ERROR] " . $this->getMessage() . "\n";
			if ($this->getPrevious() !== null) {
				$msg .= date("Y-m-d H:i:s") . " [PREVIOUS EXCEPTION] " . $this->getPrevious()->getMessage() . "\n";
			}
		}

		if (self::$logLevel >= 2) {
			$msg .= date("Y-m-d H:i:s") . " [EXCEPTION DETAILS]\n";
			$msg .= "Code: " . $this->getCode() . "\n";
			$msg .= "File: " . $this->getFile() . "\n";
			$msg .= "Line: " . $this->getLine() . "\n";
			$msg .= "Trace:\n" . $this->getTraceAsString() . "\n";
		}

		if (self::$logLevel >= 1) {
			$file = fopen("EvalMathException.log", "a");
			if ($file) {
				fwrite($file, $msg);
				fclose($file);
			} else {
				error_log("Failed to write to EvalMathException.log", 0);
			}
		}
	}
}
