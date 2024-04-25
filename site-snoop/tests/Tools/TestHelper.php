<?php

namespace Tests\Tools;

use InvalidArgumentException;
use App\Services\WebFetcher;

class TestHelper {

    static function fetchSaveOrLoad($url) {
        if(!is_string($url) || empty($url))
        {
            throw new InvalidArgumentException('URL must be a non-empty string.');
        }
        $hash = md5($url);
        $file = base_path('tests/MockData/' . $hash . '.dat');
        if (file_exists($file)) {
            return ['code' => 200, 'html' => file_get_contents($file)];
        } else {
            $fetcher = new WebFetcher();
            $response = $fetcher->fetchWebsite($url);
            if ($response['code'] === 200) {
                $file = fopen($file, "w");
                fwrite($file, $response['html']);
                fclose($file);
                return $response;
            } else {
                return null;
            }
        }
    }
}
