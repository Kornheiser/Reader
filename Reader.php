<?php

/**
 *  _  __                _          _               
 * | |/ /___  _ __ _ __ | |__   ___(_)___  ___ _ __ 
 * | ' // _ \| '__| '_ \| '_ \ / _ \ / __|/ _ \ '__|
 * | . \ (_) | |  | | | | | | |  __/ \__ \  __/ |   
 * |_|\_\___/|_|  |_| |_|_| |_|\___|_|___/\___|_|
 * 
 * Author homepage {@link https://github.com/Rollylni}
 * Kornheiser Homepage {@link https://github.com/Kornheiser}
 * 
 * @author Faruch N. <rollyllni@gmail.com>
 * @author Kornheiser Org. <kornheiser.php@gmail.com>
 * @copyright Kornheiser Org. 2021
 * @license MIT
 */
namespace Kornheiser;

use function mb_substr;
use function mb_strlen;
use function preg_match;

use const PREG_OFFSET_CAPTURE;

class Reader {
    
    protected string $encoding = "UTF-8";
    
    protected string $string = "";
    
    private string $original = "";
    
    protected int $length = 0;
    
    protected string $save = "";
    
    protected bool $lock = false;
    
    public function __construct(string $input = "", string $encoding = "UTF-8"): void {
        $this->string = $this->original = $input;
        $this->encoding = $encoding;
    }
    
    public function reset(): void {
        $this->string = $this->original;
        $this->length = 0;
        $this->save = "";
        $this->lock = false;
    }
    
    public function match(string $pattern, ?string $modifiers = null, array &$matches = [], int $flags = 0, bool $accept = true): bool {
        $result = preg_match("/^{$pattern}/{$modifiers}", $this->string, $matches, $flags);
        
        if ($result != false) {
            if ($flags & PREG_OFFSET_CAPTURE) {
                $_result = $matches[0][0];
            } else {
                $_result = $matches[0];
            }
         
            $this->length = mb_strlen($_result, $this->encoding);
            
            if ($accept) {
                $this->accept();
            }
            
            return true;
        }
        
        return false;
    }
    
    public function peekWhile(callable $callback, int $peekPart = 1): string {
        $result = "";
        
        while ($this->hasLength() && \call_user_func($callback, $this->peek($peekPart))) {
            $result .= $this->consume($peekPart);
        }
        
        return $result;
    }

    public function peek(int $length = 1, int $start = 0): ?string {
        if (!$this->hasLength()) {
            return null;
        }
        
        if ($length > ($max = $this->getStringLength())) {
            $length = $max;
        }
        
        $this->length = $length + $start;
        return mb_substr($this->string, 0, $this->length, $this->encoding);
    }
    
    public function consume(int $length = 1, int $start = 0): ?string {
        $peek = $this->peek($length, $start);
        $this->accept();
        return $peek;
    }
    
    public function accept(int $length = 0, int $start = 0): void {
        $this->save(false);
        
        $this->length += $length + $start;
        $this->string = mb_substr($this->string, $this->length, 0, $this->encoding);
        $this->length = 0;
    }
    
    public function save(bool $lock = true): void {
        if (!$this->lock) {
            $this->save = $this->string;
            $this->lock = $lock;
        }
    }
    
    /**
     * Cancel's last operation
     * 
     * @return void
     */
    public function cancel(): void {
        if ($this->save !== "") {
            $this->string = $this->save;
        }
        
        $this->length = 0;
        $this->save = "";
        $this->lock = false;
    }
    
    public function hasLength(): bool {
        return $this->getStringLength() !== 0;
    }
    
    /** 
     * 
     * @internal
     */
    public function getLength(): int {
        return $this->length;
    }
    
    public function getStringLength(): int {
        return mb_strlen($this->string, $this->encoding);
    }
    
    public function getString(): string {
        return $this->string;
    }
    
    public function getOriginal(): string {
        return $this->original;
    }
    
    public function getEncoding(): string {
        return $this->encoding;
    }
}