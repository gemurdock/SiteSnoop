<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Exception\ClientException;
use App\Services\FilterAction;
use App\Services\JSONQuery;

class WebFetcher
{
    private $client;

    // TODO: Create turn off for web fetcher if website fails too much, make sure user is notified

    public function __construct()
    {
        $this->client = new Client();
    }

    public function fetchWebsite($url)
    {
        if (!is_string($url) || empty($url)) {
            throw new InvalidArgumentException('URL must be a non-empty string.');
        }
        try {
            $response = $this->client->get($url);
            return ['html' => $response->getBody()->getContents(), 'code' => $response->getStatusCode()];
        } catch (ClientException $e) {
            $response = $e->getResponse();
            Log::error('Failed to fetch website: ' . $e->getMessage());
            // TODO: log websites that fail and their url
            return ['html' => $response->getBody()->getContents(), 'code' => $response->getStatusCode()];
        } catch (Exception $e) {
            Log::error('Failed to fetch website: ' . $e->getMessage());
            // TODO: log total failure
            return ['html' => '', 'code' => 500];
        }
    }

    public function multiFilter($html, $array)
    {
        if (!is_array($array) || empty($array)) {
            throw new InvalidArgumentException('Array must not be empty.');
        }
        $filteredHTML = $html;
        foreach ($array as $item) {
            if (!($item instanceof FilterAction)) {
                throw new InvalidArgumentException('Array must contain only FilterAction objects.');
            }
            $filteredHTML = $this->applyAction($filteredHTML, $item);
            if(is_array($filteredHTML) && count($filteredHTML) > 0) {
                $filteredHTML = $this->flattenAndClearArray($filteredHTML);
            }
        }
        return $filteredHTML;
    }

    // TODO: add JSON support
    // TODO: add ability to remove if matches, like white space

    public function applyAction($html, $filterAction)
    {
        if (!($filterAction instanceof FilterAction)) {
            throw new InvalidArgumentException('FilterAction must be a FilterAction object.');
        }
        if(is_array($html)) {
            $filteredHTML = [];
            foreach($html as $item) {
                $tmp = $this->applyAction($item, $filterAction);
                if(is_array($tmp)) {
                    $filteredHTML = array_merge($filteredHTML, $tmp);
                } else {
                    $filteredHTML[] = $tmp;
                }
            }
            return $filteredHTML;
        }
        $command = $filterAction->getCommand();
        $function = $filterAction->getFunction();
        $filter = $filterAction->getFilter();
        switch ($command) {
            case FilterAction::SELECT:
                if ($function === FilterAction::FUNC_XPATH) {
                    return $this->findAllXPath($html, $filter);
                } else if ($function === FilterAction::FUNC_REGEX) {
                    $tmp = $this->findAllRegex($html, $filter);
                    if(count($tmp) >= 2) {
                        $tmp = array_slice($tmp, 1);
                    }
                    return $tmp;
                } else if ($function === FilterAction::FUNC_JSON) {
                    return $this->findAllJSON($html, $filter);
                } else {
                    throw new InvalidArgumentException('Function must be one of the constants.');
                }
            case FilterAction::REMOVE:
                if ($function === FilterAction::FUNC_XPATH) {
                    throw new InvalidArgumentException('XPATH not supported for remove.');
                } else if ($function === FilterAction::FUNC_REGEX) {
                    return $this->removeWithRegex($html, $filter);
                } else if ($function === FilterAction::FUNC_JSON) {
                    throw new InvalidArgumentException('JSON not supported yet.');
                } else {
                    throw new InvalidArgumentException('Function must be one of the constants.');
                }
            case FilterAction::FILTER_IF_MATCH:
                return $this->filterRegex($html, $filter, true);
            case FilterAction::FILTER_IF_NOT_MATCH:
                return $this->filterRegex($html, $filter, false);
            case FilterAction::EXPECT_AMOUNT:
                $amount = intval($filter);
                if ($amount < 0) {
                    throw new InvalidArgumentException('Amount must be a positive integer.');
                }
                if (!is_array($html)) {
                    throw new InvalidArgumentException('HTML must be an array.');
                }
                return count($html) === $amount;
            case FilterAction::EXPECT_MATCH:
                return preg_match($filter, $html);
            default:
                throw new InvalidArgumentException('Command must be one of the constants.');
        }
    }

    public function findAllXPath($html, $pattern)
    {
        if (!is_string($html) || empty($html) || !is_string($pattern) || empty($pattern)) {
            throw new InvalidArgumentException('HTML and pattern must be non-empty strings.');
        }
        libxml_use_internal_errors(true); // Suppress warnings from invalid HTML
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query($pattern);

        $html_array = [];
        foreach ($elements as $element) {
            $html_array[] = $dom->saveHTML($element);
        }
        libxml_use_internal_errors(false); // Turn warnings back on
        return $html_array;
    }

    public function findAllRegex($html, $pattern)
    {
        if (!is_string($html) || empty($html)) {
            throw new InvalidArgumentException('HTML must be a non-empty string.');
        }
        if (!is_string($pattern) || empty($pattern)) {
            throw new InvalidArgumentException('Pattern must be a non-empty string.');
        }
        $matches = [];
        preg_match_all($pattern, $html, $matches);
        return $matches;
    }

    public function findAllJSON($data, $pattern)
    {
        $search = new JSONQuery($data);
        $matches = $search->query($pattern);
        return $matches;
    }

    public function removeWithRegex($html, $pattern)
    {
        if (!is_string($html) || empty($html) || !is_string($pattern) || empty($pattern)) {
            throw new InvalidArgumentException('HTML and pattern must be non-empty strings.');
        }
        return preg_replace($pattern, '', $html);
    }

    public function filterRegex($data, $pattern, $include = true)
    {
        if (!is_string($data)) {
            throw new InvalidArgumentException('Data must be a string.');
        }
        if (preg_match($pattern, $data)) {
            return $include ? null : $data;
        } else {
            return $include ? $data : null;
        }
    }

    private function flattenAndClearArray($array)
    {
        $result = [];
        foreach ($array as $element) {
            if(empty($element)) {
                continue;
            }
            if (is_array($element)) {
                $result = array_merge($result, $this->flattenAndClearArray($element));
            } else {
                $result[] = $element;
            }
        }
        return $result;
    }
}
