<?php

namespace App\Services;

class FormulaParser
{
    private $pos = -1;
    private $input;

    public function parse($input)
    {
        $this->input = $input;
        $this->pos = -1;
        $this->nextChar();
        $x = $this->parseExpression();
        if ($this->pos < strlen($this->input)) {
            throw new \Exception("Unexpected: " . $this->ch);
        }
        return $x;
    }

    private function nextChar()
    {
        $this->pos++;
        $this->ch = ($this->pos < strlen($this->input)) ? $this->input[$this->pos] : null;
    }

    private function eat($charToEat)
    {
        while ($this->ch == ' ') $this->nextChar();
        if ($this->ch == $charToEat) {
            $this->nextChar();
            return true;
        }
        return false;
    }

    private function parseExpression()
    {
        $x = $this->parseTerm();
        for (;;) {
            if ($this->eat('+')) $x += $this->parseTerm(); // addition
            elseif ($this->eat('-')) $x -= $this->parseTerm(); // subtraction
            else return $x;
        }
    }

    private function parseTerm()
    {
        $x = $this->parseFactor();
        for (;;) {
            if ($this->eat('*')) $x *= $this->parseFactor(); // multiplication
            elseif ($this->eat('/')) $x /= $this->parseFactor(); // division
            else return $x;
        }
    }

    private function parseFactor()
    {
        if ($this->eat('+')) return $this->parseFactor(); // unary plus
        if ($this->eat('-')) return -$this->parseFactor(); // unary minus

        $startPos = $this->pos;
        $x = 0;
        if ($this->eat('(')) { // parentheses
            $x = $this->parseExpression();
            $this->eat(')');
        } elseif (ctype_digit($this->ch) || $this->ch == '.') { // numbers
            while (ctype_digit($this->ch) || $this->ch == '.') $this->nextChar();
            $x = floatval(substr($this->input, $startPos, $this->pos - $startPos));
        } else {
            throw new \Exception("Unexpected: " . $this->ch);
        }

        return $x;
    }
}
