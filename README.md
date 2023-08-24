# EvalMath
**Safely evaluate math expressions.**

## Description
The EvalMath empowers safe evaluation of mathematical expressions originating from potentially untrusted sources.  
It supports built-in and user-defined functions and variables, offering flexibility for complex mathematical operations.

## Usage
```php
# Create a base instance (default precision is set to 6 digits after the decimal point)
$em = new EvalMath;

# Create a base instance with the precision parameter set to 2 digits after the decimal point
$em = new EvalMath(2);

# Basic evaluation:
$result = $em->evaluate('2+2');

# Supports: order of operation; parentheses; negation; built-in functions
$result = $em->evaluate('-8(5/2)^2*(1-sqrt(4))-8');

# Create your own variables
$em->evaluate('a = e^(ln(pi))');

# or functions
$em->evaluate('f(x,y) = x^2 + y^2 - 2x*y + 1');

# and then use them
$result = $em->evaluate('3*f(42,a)');
```

## Methods
| Method | Description |
| --- | --- |
| `$em->evaluate($expression)` | Evaluates the expression and returns the result |
| `$em->e($expression)` | A synonym for `$m->evaluate($expression)` |
| `$em->getUserVariables()` | Returns an associative array of all user-defined variables and values |
| `$em->getUserFunctions()` | Returns an array of all user-defined functions |

## Credits
This is a heavily refactored version of [EvalMath](https://github.com/dbojdo/eval-math), originally by [Daniel Bojdo](https://github.com/dbojdo), which is based on [Miles Kaufmann](http://www.twmagic.com/)'s [EvalMath](https://www.phpclasses.org/package/2695-PHP-Safely-evaluate-mathematical-expressions.html) class.

## License
This project is licensed under the BSD 3-Clause License.
The full text of the license can be found in the [LICENSE](https://github.com/Hinyka/EvalMath/blob/main/LICENSE) file.
