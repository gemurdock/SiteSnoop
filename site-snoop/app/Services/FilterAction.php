<?php

namespace App\Services;

use InvalidArgumentException;

class FilterAction {
    private $command;
    private $function;
    private $filter;

    const SELECT = '_SELECT';
    const REMOVE = '_REMOVE';
    const FILTER_IF_MATCH = '_FILTER IF MATCH';
    const FILTER_IF_NOT_MATCH = '_FILTER IF NOT MATCH';
    const EXPECT_AMOUNT = '_EXPECT AMOUNT'; // Filter must be number
    const EXPECT_MATCH = '_EXPECT MATCH';

    const FUNC_XPATH = '_XPATH';
    const FUNC_REGEX = '_REGEX';
    const FUNC_JSON = '_JSON';

    public function __construct($command, $function, $filter) {
        $this->command = $command;
        $this->function = $function;
        $this->filter = $filter;
        $this->validateCommand($command);
        $this->validateFunction($function);
    }

    public function getCommand() {
        return $this->command;
    }

    public function getFunction() {
        return $this->function;
    }

    public function getFilter() {
        return $this->filter;
    }

    private function validateCommand($command) {
        if(!is_string($command) || empty($command)) {
            throw new InvalidArgumentException('Command must be a non-empty string.');
        }
        if(!in_array($command, [self::SELECT, self::REMOVE, self::FILTER_IF_MATCH, self::FILTER_IF_NOT_MATCH, self::EXPECT_AMOUNT, self::EXPECT_MATCH])) {
            throw new InvalidArgumentException('Command must be one of the constants.');
        }
    }

    public function validateFunction($function) {
        if(!is_string($function) || empty($function)) {
            throw new InvalidArgumentException('Function must be a non-empty string.');
        }
        if(!in_array($function, [self::FUNC_XPATH, self::FUNC_REGEX, self::FUNC_JSON])) {
            throw new InvalidArgumentException('Function must be one of the constants.');
        }
    }
}
