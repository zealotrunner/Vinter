<?php

namespace Vinter;


class Token {

    private $soruce;
    private $tokens = array();
    private $next = 0;
    private $stack = 0;

    public static function i($source) {
        return new self($source);
    }

    private function __construct($source) {
        $this->source = $source;

        $r = array();
        foreach (token_get_all($source) as $token) {
            $r[] = array(
                'name'  => is_int($token[0]) ? $token[0] : $token[0],
                'value' => is_int($token[0]) ? $token[1] : $token[0],
            );
        }
        $this->tokens = $r;
    }

    public function to($token) {
        while($nextTokenName = $this->nextToken('name')) {
            $this->next++;

            if ($nextTokenName == $token) break;
        }

        return $this;
    }

    public function tryto($token) {
        $current = $this->next;
        while($nextTokenName = $this->nextToken('name')) {
            $this->next++;

            if ($nextTokenName == $token) break;
        }

        if ($this->end()) {
            $this->next = $current;
        }

        return $this;
    }

    public function collectBetween($startToken, $endToken, $back) {
        $this->skipBlanks();
        if ($this->nextToken('value') != $startToken) return $this;

        $collects = array();
        while($nextToken = $this->nextToken()) {

            $collects[] = $nextToken;

            $this->next++;

            if ($this->end() || $this->matched($nextToken['name'], $endToken)) break;
        }

        // remove head and tail
        $collects = array_slice($collects, 1, count($collects) - 2);

        call_user_func($back, $collects);

        return $this;
    }

    private function skipBlanks() {
        while($nextTokenName = $this->nextToken('name')) {
            if ($nextTokenName != T_WHITESPACE) break;

            $this->next++;
        }
    }

    private function nextToken($type = null) {
        if ($this->end()) return null;

        return $type
            ? $this->tokens[$this->next][$type]
            : $this->tokens[$this->next];
    }

    private function end() {
        return count($this->tokens) <= $this->next;
    }

    private function matched($nextToken, $endToken) {
        $matches = array(
            ')' => '(',
            '}' => '{'
        );

        $matchingToken = $matches[$endToken];
        if ($matchingToken && $nextToken == $matchingToken) {
            // print 'stack++' . "\n";
            $this->stack++;
        }

        if ($matchingToken && $nextToken == $endToken) {
            // print 'stack--'. "\n";
            $this->stack--;
        }

        if ($this->stack == 0 && $nextToken == $endToken) {
            return true;
        } else  {
            return false;
        }

    }

}


