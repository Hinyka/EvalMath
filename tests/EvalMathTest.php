<?php

namespace Hinyka\EvalMath\Tests;

use PHPUnit\Framework\TestCase;
use Hinyka\EvalMath\EvalMath;
use Hinyka\EvalMath\EvalMathException;

/**
 * Class EvalMathTest
 *
 * @package Hinyka\EvalMath
 * @author Karel Hink
 * @license BSD-3-Clause
 * @link https://github.com/Hinyka/EvalMath
 * @version 2.0.0
 */
class EvalMathTest extends TestCase {
	/**
	 * @var EvalMath
	 */
	private EvalMath $evalMath;

	protected function setUp(): void {
		$this->evalMath = new EvalMath(6);
	}

	/**
	 * @test
	 * @throws EvalMathException
	 */
	public function testModuloOperator(): void {
		$this->evalMath->evaluate('a = 9');
		$this->evalMath->evaluate('b = 3');
		$this->assertSame(0.0, $this->evalMath->evaluate('a%b')); // 9%3 => 0

		$this->evalMath->evaluate('a = 10');
		$this->evalMath->evaluate('b = 3');
		$this->assertSame(1.0, $this->evalMath->evaluate('a%b')); // 10%3 => 1

		$this->evalMath->evaluate('a = 10');
		$this->evalMath->evaluate('b = 7');
		$this->evalMath->evaluate('c = -2');
		$this->evalMath->evaluate('d = 2');
		$this->assertSame(9.0, $this->evalMath->evaluate('10-a%(b+c*d)')); // 10-10%(7-2*2) => 9
	}

	/**
	 * @throws EvalMathException
	 */
	public function testDoubleMinusAsPlus(): void {
		$this->evalMath->evaluate('a = 1');
		$this->evalMath->evaluate('b = 2');
		$this->evalMath->evaluate('c = 3');
		$this->evalMath->evaluate('d = 4');
		$this->assertSame(11.0, $this->evalMath->evaluate('a+b*c--d')); // 1+2*3--4 => 1+6+4 => 11

		$this->evalMath->evaluate('d = -4');
		$this->assertSame(3.0, $this->evalMath->evaluate('a+b*c--d')); // 1+2*3---4 => 1+6-4 => 3
	}

	/**
	 * @throws EvalMathException
	 */
	public function testAddition(): void {
		$this->assertSame(4.0, $this->evalMath->evaluate('2 + 2'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testSubtraction(): void {
		 $this->assertSame(2.0, $this->evalMath->evaluate('4 - 2'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testMultiplication(): void {
		$this->assertSame(4.0, $this->evalMath->evaluate('2 * 2'));
	}


	/**
	 * @throws EvalMathException
	 */
	public function testDivision(): void {
		$this->assertSame(2.0, $this->evalMath->evaluate('4 / 2'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testExponentiation(): void {
		$this->assertSame(4.0, $this->evalMath->evaluate('2 ^ 2'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testSquareRoot(): void {
		$this->assertSame(2.0, $this->evalMath->evaluate('sqrt(4)'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testAdditionFP(): void {
		$this->assertSame(4.1, $this->evalMath->evaluate('2 + 2.1'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testSubtractionFP(): void {
		$this->assertSame(2.5, $this->evalMath->evaluate('4.5 - 2'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testMultiplicationFP(): void {
		$this->assertSame(4.5, $this->evalMath->evaluate('2.25 * 2'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testDivisionFP(): void {
		$this->assertSame(4.2, $this->evalMath->evaluate('10.5 / 2.5'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testExponentiationFP(): void {
		$this->assertSame(4.4521, $this->evalMath->evaluate('2.11 ^ 2'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testSquareRootFP(): void {
		$this->assertSame(2.109502, $this->evalMath->evaluate('sqrt(4.45)'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testNegativeNumbers(): void {
		$this->assertSame(-5.0, $this->evalMath->evaluate('2 * -2.5'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testCombinedOperations(): void {
		$this->assertSame(8.0, $this->evalMath->evaluate('2 + 3 * 2'));
		$this->assertSame(18.0, $this->evalMath->evaluate('(4 + 2) * 3'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testExpressionEvaluation(): void {
		$this->assertSame(42.0, $this->evalMath->evaluate('-8*(5/2)^2*(1-sqrt(4))-8'));
	}

	/**
	 * @throws EvalMathException
	 */
	public function testVariableAssignment(): void {
		$this->evalMath->evaluate('x = 5.5');
		$this->assertSame(5.5, $this->evalMath->evaluate('x'));
		$this->assertSame(11.0, $this->evalMath->evaluate('x * 2'));

		$vars = $this->evalMath->getUserVariables();
		$this->assertIsArray($vars);
		$this->assertArrayHasKey('x', $vars);
		$this->assertSame(5.5, $vars['x']);
	}
	public function testInvalidExpression(): void {
		$this->expectException(EvalMathException::class);
		$this->evalMath->evaluate('1 + ');
	}

	public function testExceptions(): void {
		$this->expectException(EvalMathException::class);
		$this->evalMath->evaluate('1 / 0');
	}
}
