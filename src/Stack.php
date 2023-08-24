<?php

namespace Hinyka\EvalMath;

/**
 * Class Stack
 *
 * @package Hinyka\EvalMath
 * @author Karel Hink <info@karelhink.cz>
 * @author Daniel Bojdo <daniel.bojdo@8x8.com>
 * @author Miles Kaufmann <http://www.twmagic.com/>
 * @copyright Copyright (c) 2023 Karel Hink
 * @copyright Copyright (c) 2016 Daniel Bojdo
 * @copyright Copyright (c) 2005 Miles Kaufmann
 * @license LICENSE BSD-3-Clause
 * @link https://github.com/Hinyka/EvalMath
 * @version 2.0.0
 */
class Stack {
	/**  @var int $precision The number of digits after the decimal point */
	private int $precision;

	/** @var array<int, mixed> */
	public array $stack = [];

	/** @var int */
	public int $count = 0;

	/**
	 * @param int $precision
	 */
	public function __construct(int $precision = 6) {
		 $this->precision = $precision;
	}

	/**
	 * @param mixed $val
	 * @return void
	 */
	public function push(mixed $val): void {
		if (is_numeric($val)) {
			$this->stack[ $this->count ] = round((float)$val, $this->precision);
		} else {
			$this->stack[ $this->count ] = $val;
		}
		$this->count++;
	}

	/**
	 * @return float|null|string
	 * @throws EvalMathException
	 */
	public function pop(): float|null|string {
		if ($this->count > 0) {
			$this->count--;
			$value = $this->stack[ $this->count ];

			if (is_numeric($value)) {
				return floatval($value);
			} elseif (is_string($value)) {
				return $value;
			} else {
				throw new EvalMathException("Internal error: Invalid value encountered while poping from stack");
			}
		} else {
			return null;
		}
	}

	/**
	 * @param int $n
	 * @return mixed
	 */
	public function last(int $n = 1): mixed {
		$key = $this->count - $n;

		return array_key_exists($key, $this->stack) ? $this->stack[$key] : null;
	}
}
