<?php

namespace App\Services;

use InvalidArgumentException;
use Exception;
use Throwable;

class QueryResultNotFoundException extends Exception {
    public function __construct($message = 'Query result not found.', $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

class JSONQuery {
    private $data;

    public function __construct($jsonData) {
        if($jsonData === null || !is_string($jsonData) || empty($jsonData)) {
            throw new InvalidArgumentException('Data must be a non-empty string.');
        }
        $this->JSONToArray($jsonData);
    }

    /*
        Query JSON data

        @param string $query
        @param bool $decode - default true to return an array else return JSON string
        @return array or JSON depending on $decode
    */
    public function query($query, $decode=true) {
        $parsedQuery = $this->parseQuery($query);
        $result = $this->querySearch($parsedQuery);
        if($decode) {
            return $result;
        }
        return json_encode($result);
    }

    private function JSONToArray($jsonData) {
        $decodedJSON = json_decode($jsonData, true);
        if(json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Data must be valid JSON.');
        }
        $this->data = $decodedJSON;
    }

    private function parseQuery($query) {
        if(!is_string($query) || empty($query)) {
            throw new InvalidArgumentException('Query must be a non-empty string.');
        }

        if($query[0] !== '$') {
            throw new InvalidArgumentException('Query must start with root represented by \'$\'');
        }

        $parts = preg_split('/(?<!\\\)\./', $query); // explode only for '.', not '\.'
        $parts = array_map(function($part) { // Remove excaping backslashes
            return str_replace('\.', '.', $part);
        }, $parts);

        $parts = array_map(function($part) { // Split by [index] and text before
            if(preg_match('/\[.+\]$/', $part)) {
                preg_match('/^([^\[]+)((?:\[.+\])+)/', $part, $matches);
                $matches[2] = preg_split('/(\[\d+\])/', $matches[2], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                $part = [$matches[1], $matches[2]];
            }
            return $part;
        }, $parts);

        $parts = $this->array_flatten($parts);

        return $parts;
    }

    private function querySearch($queryParts) {
        $result = json_decode(json_encode($this->data), true); // deep copy
        if(!is_array($queryParts)) {
            throw new InvalidArgumentException('Query tokens must be an array.');
        }
        foreach ($queryParts as $token) {
            if($token === '$') {
                continue;
            } else if(preg_match('/\[.+\]$/', $token)) {
                $index = preg_replace('/\[(.+)\]$/', '$1', $token); // get index from [index]
                if(!is_numeric($index)) {
                    throw new InvalidArgumentException('Array index must be a number.');
                }
                $index = (int)$index;
                if($index < 0) {
                    throw new InvalidArgumentException('Array index must be a non-negative integer.');
                }
                if($index > count($result) - 1) {
                    throw new \OutOfRangeException('Array index out of bounds.');
                }
                if(!array_key_exists($index, $result)) {
                    throw new QueryResultNotFoundException();
                }
                $result = $result[$index];
            } else {
                if(!is_array($result)) {
                    throw new QueryResultNotFoundException();
                }
                if(!array_key_exists($token, $result)) {
                    throw new QueryResultNotFoundException();
                }
                $result = $result[$token];
            }
        }
        return $result;
    }

    private function array_flatten($array) {
        $result = [];
        foreach ($array as $element) {
            if (is_array($element)) {
                $result = array_merge($result, $this->array_flatten($element));
            } else {
                $result[] = $element;
            }
        }
        return $result;
    }
}
