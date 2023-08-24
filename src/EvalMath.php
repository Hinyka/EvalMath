<?php

namespace Hinyka\EvalMath;

/**
 * Class EvalMath
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
class EvalMath {
	/**  @var int $precision The number of digits after the decimal point */
	private int $precision;

	/** @var array<string, float> $variablesAndConstants Variables and constants */
	private array $variablesAndConstants = [];

	/** @var array<string, array<string, array<int, string>>> $userDefinedFunctions User-defined functions */
	private array $userDefinedFunctions = [];

	/** @var array<int, string> $constants Constants */
	public array $constants = ['e', 'pi'];

	/** @var array<int, string> $builtInFunctions Built-in functions */
	public array $builtInFunctions = [
		'sin', 'sinh', 'arcsin', 'asin', 'arcsinh', 'asinh',
		'cos', 'cosh', 'arccos', 'acos', 'arccosh', 'acosh',
		'tan', 'tanh', 'arctan', 'atan', 'arctanh', 'atanh',
		'sqrt', 'abs', 'ln', 'log', 'log10', 'floor', 'ceil'
	];

	/**
	 * @param int $precision
	 */
	public function __construct(int $precision = 6) {
		$this->precision = $precision;

		// Initialize the constants
		$this->variablesAndConstants['pi'] = pi();
		$this->variablesAndConstants['e'] = exp(1);
	}

	/**
	 * Alias for evaluate() function
	 *
	 * @param string $expression
	 * @return bool|float|int
	 * @throws EvalMathException
	 */
	public function e(string $expression): bool|float|int {
		return $this->evaluate($expression);
	}

	/**
	 * Evaluates the expression and returns the result
	 *
	 * @param string $expression
	 * @return bool|float|int
	 * @throws EvalMathException
	 */
	public function evaluate(string $expression): bool|float|int {
		$expression = trim($expression);

		if (str_ends_with($expression, ';')) {
			$expression = substr($expression, 0, strlen($expression) - 1); // strip semicolons at the end
		}

		if ($expression === '') {
			throw new EvalMathException('Input expression cannot be empty');
		} else {
			return $this->processExpressionAssignment($expression);
		}
	}

	/**
	 * Returns an associative array of all user-defined variables and values
	 *
	 * @return array<string, float>
	 */
	public function getUserVariables(): array {
		$userVariables = $this->variablesAndConstants;
		unset($userVariables['pi'], $userVariables['e']);
		return $userVariables;
	}

	/**
	 * Returns an array of all user-defined functions
	 *
	 * @return array<string>
	 */
	public function getUserFunctions(): array {
		$userFunctions = [];
		foreach ($this->userDefinedFunctions as $functionName => $functionData) {
			if (is_array($functionData) && array_key_exists('args', $functionData) && is_array($functionData['args'])) {
				$userFunctions[] = $functionName . '(' . implode(',', $functionData['args']) . ')';
			}
		}

		return $userFunctions;
	}

	private function isVariableAssignment(string $expression): bool {
		return (bool) preg_match('/^\s*([a-z]\w*)\s*=\s*(.+)$/', $expression);
	}

	private function isFunctionAssignment(string $expression): bool {
		return (bool) preg_match('/^\s*([a-z]\w*)\s*\(\s*([a-z]\w*(?:\s*,\s*[a-z]\w*)*)\s*\)\s*=\s*(.+)$/', $expression);
	}

	/**
	 * @throws EvalMathException
	 */
	private function processExpressionAssignment(string $expression): bool|float {
		if ($this->isVariableAssignment($expression)) {
			preg_match('/^\s*([a-z]\w*)\s*=\s*(.+)$/', $expression, $matches);
			return $this->processVariableAssignment($matches[1], $matches[2]);
		} elseif ($this->isFunctionAssignment($expression)) {
			preg_match('/^\s*([a-z]\w*)\s*\(\s*([a-z]\w*(?:\s*,\s*[a-z]\w*)*)\s*\)\s*=\s*(.+)$/', $expression, $matches);
			return $this->processFunctionAssignment($matches[1], $matches[2], $matches[3]);
		} else {
			return $this->evaluateExpression($expression);
		}
	}

	/**
	 * @throws EvalMathException
	 */
	private function processVariableAssignment(string $variable, string $expression): float {
		if (in_array($variable, $this->constants)) {
			throw new EvalMathException("Cannot assign to constant '$variable'");
		}
		$result = $this->evaluatePostfixExpression($this->convertInfixToPostfix($expression));
		if ($result !== false) {
			if (is_numeric($result)) {
				$this->variablesAndConstants[$variable] = (float) $result;
				return $this->variablesAndConstants[$variable];
			} else {
				throw new EvalMathException("Failed to assign variable '$variable', value must be numeric");
			}
		} else {
			throw new EvalMathException("Failed to assign variable '$variable'");
		}
	}

	/**
	 * @throws EvalMathException
	 */
	private function processFunctionAssignment(string $function, string $arguments, string $expression): bool {
		if (in_array($function, $this->builtInFunctions)) {
			throw new EvalMathException("Cannot redefine built-in function '$function()'");
		}

		$string = preg_replace("/\s+/", "", $arguments);
		if ($string !== null) {
			$arguments = explode(",", $string); // get the arguments
		} else {
			//$args = []; // no args
			throw new EvalMathException("Function '$function' must have at least one argument");
		}

		$stack = $this->convertInfixToPostfix($expression);
		if ($stack) {
			for ($i = 0; $i < count($stack); $i++) { // freeze the state of the non-argument variables
				$token = $stack[$i];
				if (!is_string($token)) {
					$token = strval($token);
				}
				if (preg_match('/^[a-z]\w*$/', $token) && !in_array($token, $arguments)) {
					if (array_key_exists($token, $this->variablesAndConstants)) {
						$stack[$i] = strval($this->variablesAndConstants[$token]);
					} else {
						throw new EvalMathException("Undefined variable '$token' in function definition");
					}
				}
			}
			$this->userDefinedFunctions[$function] = ['args' => $arguments, 'func' => $stack];
			return true;
		}

		return false;
	}

	/**
	 * @throws EvalMathException
	 */
	private function evaluateExpression(string $expression): float {
		$postfixTokens = $this->convertInfixToPostfix($expression);
		if ($postfixTokens) {
			$result = $this->evaluatePostfixExpression($postfixTokens);
			if (is_numeric($result)) {
				return floatval($result);
			} else {
				throw new EvalMathException("Failed to evaluate expression '$expression'");
			}
		} else {
			throw new EvalMathException("Failed to evaluate expression '$expression'");
		}
	}

	/**
	 * Convert infix to postfix notation
	 *
	 * @param string $expression
	 * @return array<int, string>
	 * @throws EvalMathException
	 */
	private function convertInfixToPostfix(string $expression): array {

		$index = 0;
		$stack = new Stack($this->precision);
		$postfixTokens = []; // postfix form of expression, to be passed to evaluatePostfixExpression()
		$expression = trim($expression);

		$ops = ['+', '-', '*', '/', '^', '_', '%'];
		$ops_r = ['+' => 0, '-' => 0, '*' => 0, '/' => 0, '^' => 1, '%' => 0]; // right-associative operator?
		$ops_p = ['+' => 0, '-' => 0, '*' => 1, '/' => 1, '_' => 1, '^' => 2, '%' => 1]; // operator precedence

		$expecting_op = false; // we use this in syntax-checking the expression and determining when a - is a negation
		$allow_neg = true;

		if (preg_match('/[^\%\w\s+*^\/()\.,-]/', $expression, $matches)) { // make sure the characters are all good
			throw new EvalMathException("Illegal character '{$matches[0]}'");
		}

		while (1) { // 1 Infinite Loop ;)
			$op = substr($expression, $index, 1); // get the first character at the current index

			// find out if we're currently at the beginning of a number/variable/function/parenthesis/operand
			$ex = preg_match('/^([A-Za-z]\w*\(?|\d+(?:\.\d*)?|\.\d+|\()/', substr($expression, $index), $match);

			if ($op == '-' && !$expecting_op) { // is it a negation instead of a minus?
				$stack->push('_'); // put a negation on the stack
				$index++;
			} elseif ($op == '_') { // we have to explicitly deny this, because it's legal on the stack
				throw new EvalMathException("Illegal character '_'"); // but not in the input expression
			} elseif (($ex || in_array($op, $ops)) && $expecting_op) { // are we putting an operator on the stack?
				if ($ex) { // are we expecting an operator but have a number/variable/function/opening parenthesis?
					$op = '*';
					$index--; // it's an implicit multiplication
				}
				// heart of the algorithm:
				while ($stack->count > 0 && ($o2 = $stack->last()) && in_array($o2, $ops) && ($ops_r[$op] ? $ops_p[$op] < $ops_p[$o2] : $ops_p[$op] <= $ops_p[$o2])) {
					$postfixTokens[] = $stack->pop(); // pop stuff off the stack into the postfixTokens
				}
				// many thanks: http://en.wikipedia.org/wiki/Reverse_Polish_notation#The_algorithm_in_detail
				$stack->push($op); // finally put OUR operator onto the stack
				$index++;
				$expecting_op = false;
			} elseif ($op == ')' && $expecting_op) { // ready to close a parenthesis?
				$this->processClosingParenthesis($stack, $postfixTokens);
				$index++;
			} elseif ($op == ',' && $expecting_op) { // did we just finish a function argument?
				$this->processComma($stack, $postfixTokens);
				$index++;
				$expecting_op = false;
			} elseif ($op == '(' && !$expecting_op) {
				$stack->push('('); // that was easy
				$index++;
				$allow_neg = true;
			} elseif ($ex && !$expecting_op) { // do we now have a function/variable/number?
				$expecting_op = true;
				$this->processFunctionVariableNumber($match[1], $stack, $postfixTokens, $expecting_op);
				$index += strlen($match[1]);
			} elseif ($op == ')') { // miscellaneous error checking
				throw new EvalMathException("Unexpected ')'");
			} elseif (in_array($op, $ops) && !$expecting_op) {
				throw new EvalMathException("Unexpected operator '$op'");
			} else { // I don't even want to know what you did to get here
				throw new EvalMathException("An unexpected error occurred");
			}
			if ($index == strlen($expression)) {
				if (in_array($op, $ops)) { // did we end with an operator? bad.
					throw new EvalMathException("Operator '$op' lacks operand");
				} else {
					break;
				}
			}
			while (substr($expression, $index, 1) == ' ') { // step the index past whitespace (pretty much turns whitespace into implicit multiplication if no operator is there)
				$index++;
			}
		}

		$this->processRemainingOperators($stack, $postfixTokens);

		return $postfixTokens;
	}

	/**
	 * @param array<int, string> $postfixTokens
	 * @throws EvalMathException
	 */
	private function processClosingParenthesis(Stack $stack, array &$postfixTokens): void {
		while (($o2 = $stack->pop()) != '(') { // pop off the stack back to the last (
			if (is_null($o2)) {
				throw new EvalMathException("Unexpected ')'");
			} else {
				$postfixTokens[] = $o2;
			}
		}

		$lastValue = $stack->last(2); // get the last two values on the stack
		if (is_scalar($lastValue)) {
			$lastValueAsString = strval($lastValue);

			if (preg_match("/^([A-Za-z]\w*)\($/", $lastValueAsString, $matches)) { // did we just close a function?
				$function = $matches[1]; // get the function name
				$arg_count = $stack->pop(); // see how many arguments there were (cleverly stored on the stack, thank you)
				$postfixTokens[] = $stack->pop(); // pop the function and push onto the output
				if (in_array($function, $this->builtInFunctions)) { // check the argument count
					if ($arg_count > 1) {
						throw new EvalMathException("Too many arguments (" . $arg_count . " given, 1 expected)");
					}
				} elseif (array_key_exists($function, $this->userDefinedFunctions)) {
					if ($arg_count != count($this->userDefinedFunctions[$function]['args'])) {
						throw new EvalMathException("Wrong number of arguments (" . $arg_count . " given, " . count($this->userDefinedFunctions[$function]['args']) . " expected)");
					}
				} else { // did we somehow push a non-function on the stack? this should never happen
					throw new EvalMathException("Internal error");
				}
			}
		}
	}

	/**
	 * @param array<int, string> $postfixTokens
	 * @throws EvalMathException
	 */
	private function processComma(Stack $stack, array &$postfixTokens): void {
		while (($o2 = $stack->pop()) != '(') {
			if (is_null($o2)) {
				throw new EvalMathException("Unexpected ','"); // oops, never had a (
			} else {
				$postfixTokens[] = $o2; // pop the argument expression stuff and push onto the output
			}
		}

		$lastValue = $stack->last(2); // get the last two values on the stack
		if (is_scalar($lastValue)) {
			$lastValueAsString = strval($lastValue);
			// make sure there was a function
			if (!preg_match("/^([A-Za-z]\w*)\($/", $lastValueAsString)) {
				throw new EvalMathException("Unexpected ','");
			}
		}

		$stack->push((int) $stack->pop() + 1); // increment the argument count
		$stack->push('('); // put the ( back on, we'll need to pop back to it again
	}

	/**
	 * @param array<int, string> $postfixTokens
	 */
	private function processFunctionVariableNumber(string $val, Stack $stack, array &$postfixTokens, bool &$expecting_op): void {
		if (preg_match("/^([A-Za-z]\w*)\($/", $val, $matches)) {
			if (in_array($matches[1], $this->builtInFunctions) || array_key_exists($matches[1], $this->userDefinedFunctions)) {
				$stack->push($val);
				$stack->push(1);
				$stack->push('(');
				$expecting_op = false;
			} else {
				$val = $matches[1];
				$postfixTokens[] = $val;
			}
		} else {
			$postfixTokens[] = $val;
		}
	}

	/**
	 * @param array<int, string> $postfixTokens
	 * @throws EvalMathException
	 */
	private function processRemainingOperators(Stack $stack, array &$postfixTokens): void {
		while (null !== $op = $stack->pop()) { // pop everything off the stack and push onto output
			if ($op == '(') {
				throw new EvalMathException("Expecting ')'"); // if there are (s on the stack, ()s were unbalanced
			} else {
				$postfixTokens[] = $op;
			}
		}
	}

	/**
	 * Evaluate postfix notation
	 *
	 * @param array<string> $tokens
	 * @param array<float|int|string> $vars
	 * @return mixed
	 * @throws EvalMathException
	 */
	private function evaluatePostfixExpression(array $tokens, array $vars = []): mixed {
		if (empty($tokens)) {
			throw new EvalMathException("Internal error: No tokens to process");
		}

		$stack = new Stack($this->precision);

		foreach ($tokens as $token) { // nice and easy
			// if the token is a binary operator, pop two values off the stack, do the operation, and push the result back on
			if (in_array($token, ['+', '-', '*', '/', '^', '%'])) {
				$this->processBinaryOperator($token, $stack);
			} elseif ($token == "_") { // if the token is a unary operator, pop one value off the stack, do the operation, and push it back on
				$this->processUnaryOperator($stack);
			} elseif (preg_match("/^([a-z]\w*)\($/", $token, $matches)) { // if the token is a function, pop arguments off the stack, hand them to the function, and push the result back on
				$this->processFunctionCall($matches[1], $stack);
			} else { // if the token is a number or variable, push it on the stack
				$this->processNumberOrVariable($token, $vars, $stack);
			}
		}

		return $this->finalizeEvaluation($stack); // when we're out of tokens, the stack should have a single element, the final result
	}

	/**
	 * @throws EvalMathException
	 */
	private function processBinaryOperator(string $token, Stack $stack): void {
		$op2 = $stack->pop();
		$op1 = $stack->pop();

		if ($op1 === null || $op2 === null) {
			throw new EvalMathException("Internal error: Insufficient operands for operator '$token'");
		} elseif (!is_float($op1) || !is_float($op2)) {
			throw new EvalMathException("Internal error: Non-float operands for operator '$token'");
		}

		switch ($token) {
			case '+':
				$stack->push($op1 + $op2);
				break;
			case '-':
				$stack->push($op1 - $op2);
				break;
			case '*':
				$stack->push($op1 * $op2);
				break;
			case '/':
				if ($op2 == 0) {
					throw new EvalMathException("Division by zero");
				}
				$stack->push($op1 / $op2);
				break;
			case '^':
				$stack->push(pow($op1, $op2));
				break;
			case '%':
				$stack->push($op1 % $op2);
				break;
		}
	}

	/**
	 * @throws EvalMathException
	 */
	private function processUnaryOperator(Stack $stack): void {
		$stack->push(-1 * (float) $stack->pop());
	}

	/**
	 * @throws EvalMathException
	 */
	private function processFunctionCall(string $function, Stack $stack): void {
		if (in_array($function, $this->builtInFunctions)) {
			$op1 = $stack->pop();
			if ($op1 === null) {
				throw new EvalMathException("Internal error: Insufficient operands for function '$function'");
			}
			$result = $this->processBuiltInFunction($function, (float) $op1);
			$stack->push($result);
		} elseif (array_key_exists($function, $this->userDefinedFunctions)) {
			$this->processUserDefinedFunction($function, $stack);
		}
	}


	/**
	 * @throws EvalMathException
	 */
	private function processBuiltInFunction(string $function, float $operand): float {
		$function = preg_replace("/^arc/", "a", $function);
		if ($function == 'ln') {
			$function = 'log';
		}
		if (in_array($function, $this->builtInFunctions)) {
			if (is_callable($function)) {
				return $function($operand);
			} else {
				throw new EvalMathException("Function '$function' is not callable");
			}
		} else {
			throw new EvalMathException("Unknown built-in function: $function");
		}
	}

	/**
	 * @throws EvalMathException
	 */
	private function processUserDefinedFunction(string $function, Stack $stack): void {
		$args = [];
		foreach ($this->userDefinedFunctions[$function]['args'] as $argName) {
			$argValue = $stack->pop();
			if (is_null($argValue)) {
				throw new EvalMathException("Internal error: Null value encountered while evaluating user-defined function '$function'");
			}
			$args[$argName] = $argValue;
		}
		$result = $this->evaluatePostfixExpression($this->userDefinedFunctions[$function]['func'], $args);
		$stack->push($result);
	}


	/**
	 * @param array<float|int|string> $vars
	 * @throws EvalMathException
	 */
	private function processNumberOrVariable(string $token, array $vars, Stack $stack): void {
		if (is_numeric($token)) {
			$stack->push($token);
		} elseif (array_key_exists($token, $this->variablesAndConstants)) {
			$stack->push($this->variablesAndConstants[$token]);
		} elseif (array_key_exists($token, $vars)) {
			$stack->push($vars[$token]);
		} else {
			throw new EvalMathException("Undefined variable '$token'");
		}
	}

	/**
	 * @throws EvalMathException
	 */
	private function finalizeEvaluation(Stack $stack): string|null|float {
		if ($stack->count != 1) {
			throw new EvalMathException("Internal error: Inconsistent stack state at the end of evaluation");
		}
		return $stack->pop();
	}
}
