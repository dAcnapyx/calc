<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class CalculatorController extends AbstractController
{
    private function infix_to_rpn($input) # Shunting-yard algorithm
    {
        $output_queue = [];

        if ( !empty($input) ) {
            $precedence = [
                '+' => 2,
                '-' => 2,
                '/' => 3,
                '*' => 3,
                '%' => 3,
                '^' => 4
            ];
            define('LEFT', 0);
            define('RIGHT', 1);
            $assoc = [
                '+' => LEFT,
                '-' => LEFT,
                '/' => LEFT,
                '*' => LEFT,
                '%' => LEFT,
                '^' => RIGHT
            ];
            $whitespace = " \t\n";
            $operators = implode('', array_keys($precedence));
            $simpletokens = $operators.'()';
            $numbers = "0123456789.";
            $precedence['('] = 0;
            $precedence[')'] = 0;
            $tokens = [];

            for ($i = 0; isset($input[$i]); $i++) {
                $chr = $input[$i];
                if (strstr($whitespace, $chr)) {
                    // noop whitespace
                } elseif (strstr($simpletokens, $chr)) {
                    $tokens[] = $chr;
                } elseif (strstr($numbers, $chr)) {
                    $number = $chr;
                    while(isset($input[$i+1]) && strstr($numbers, $input[$i+1])) {
                        $number .= $input[++$i];
                    }
                    $tokens[] = floatval($number);
                } else {
                    $error = "Invalid character at position $i: $input[$i]\n$input\n" .
                    str_pad('^', $i+1, ' ', STR_PAD_LEFT) . ' (error here)';
                }
            }

            $operator_stack = [];
            while ($tokens) {
                $token = array_shift($tokens);
                if (is_float($token)) {
                    $output_queue[] = $token;
                } elseif (strstr($operators, $token)) {
                    while ($operator_stack && 
                        $precedence[end($operator_stack)] >= $precedence[$token] + $assoc[$token]) {
                        $output_queue[] = array_pop($operator_stack);
                    }
                    $operator_stack[] = $token;
                } elseif ($token === '(') {
                    $operator_stack[] = $token;
                } elseif ($token === ')') {
                    while (end($operator_stack) !== '(') {
                        $output_queue[] = array_pop($operator_stack);
                        if (!$operator_stack) {
                            $error = "Mismatched parentheses!";
                            
                        }
                    }
                    array_pop($operator_stack);
                } else {
                    $error = "Unexpected token $token";
                    
                }
            }

            while ($operator_stack) {
                $token = array_pop($operator_stack);
                
                if ($token === '(') {
                    $error = "Mismatched parentheses!";
                }
                
                $output_queue[] = $token;
            }
        }

        return implode(' ', $output_queue);
    }

    private function calc_rpn($postFixStr)
    {
        $stack = [];
        $token = explode(" ", trim($postFixStr));
        $count = count($token);
        $err = '';
     
        for($i = 0 ; $i<$count;$i++)
        {
            $tokenNum = "";
     
            if (is_numeric($token[$i])) {
                array_push($stack,$token[$i]);
            }
            else
            {
                $secondOperand = end($stack);
                array_pop($stack);
                $firstOperand = end($stack);
                array_pop($stack);
     
                if ($token[$i] == "*")
                    array_push($stack,$firstOperand * $secondOperand);
                else if ($token[$i] == "/")
                    if ($secondOperand == 0)
                        $err = 'Error: Devision by zero';
                    else
                    array_push($stack,$firstOperand / $secondOperand);
                else if ($token[$i] == "-")
                    array_push($stack,$firstOperand - $secondOperand);
                else if ($token[$i] == "+")
                    array_push($stack,$firstOperand + $secondOperand);
                else if ($token[$i] == "^")
                    array_push($stack,pow($firstOperand,$secondOperand));
                else {
                    $err = 'Error';
                }
            }
        }

        if (!empty($err))
            return $err;
        else
            return end($stack);
    }

    public function calculator(Request $request)
    {
        $error = '';
        $result = '';
        $calc_str = $request->query->get('calc_str');
        
        $reverse_polish_notation = $this->infix_to_rpn($calc_str);

        if ( strpos($reverse_polish_notation, 'Error') !== false )
            $error = $reverse_polish_notation;
        else
            $result = $this->calc_rpn($reverse_polish_notation);

        return $this->render('calculator/index.html.twig', [
            'calc_str' => $calc_str
            , 'result' => $result
            , 'error' => $error
        ]);
    }
}
