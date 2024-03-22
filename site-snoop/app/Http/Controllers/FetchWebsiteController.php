<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;

class FetchWebsiteController extends Controller
{
    public function fetch()
    {
        $fetch_this = 'https://jsonplaceholder.typicode.com/posts';

        $client = new Client();
        $response = $client->request('GET', $fetch_this);
        $html = $response->getBody()->getContents();

        return $html;
    }
}
