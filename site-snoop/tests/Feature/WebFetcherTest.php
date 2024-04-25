<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\WebFetcher;
use App\Services\FilterAction;
use Tests\Tools\TestHelper;

class WebFetcherTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_main_page_reachable(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    // TODO: test edge cases for each

    /**
     * @group external_resource
     */
    public function test_guzzle_fetch(): void
    {
        $websites = [
            "https://www.google.com/",
            "https://www.apple.com/",
            "https://www.youtube.com/",
            "https://www.amazon.com/",
            "https://www.cnn.com"
        ];

        foreach ($websites as $website) {
            $fetcher = new WebFetcher();
            $response = $fetcher->fetchWebsite($website);
            $this->assertEquals(200, $response['code']);
            $this->assertNotEmpty(strlen($response['html']) > 10);
        }
    }

    /**
     * @group external_resource
     */
    public function test_guzzle_fetch_fail(): void
    {
        $fetcher = new WebFetcher();
        $response = $fetcher->fetchWebsite("https://www.google.com/thispagedoesnotexist");
        $this->assertTrue($response['code'] === 404);
    }

    /**
     * @group external_resource
     */
    public function test_guzzle_fetch_does_not_exist(): void
    {
        $fetcher = new WebFetcher();
        $response = $fetcher->fetchWebsite("https://thiswebsitedoesnotexist2302983.com");
        $this->assertEquals(500, $response['code']);
        $this->assertEquals("", $response['html']);
    }

    public function test_xpath(): void
    {
        $fetcher = new WebFetcher();
        $html = TestHelper::fetchSaveOrLoad("https://en.wikipedia.org/wiki/PHP");
        $filter = new FilterAction(FilterAction::SELECT, FilterAction::FUNC_XPATH, '//title');
        $result = $fetcher->applyAction($html['html'], $filter);
        $this->assertEquals('<title>PHP - Wikipedia</title>', $result[0]);
    }

    public function test_regex(): void
    {
        $fetcher = new WebFetcher();
        $html = TestHelper::fetchSaveOrLoad("https://en.wikipedia.org/wiki/PHP");
        $filter = new FilterAction(FilterAction::SELECT, FilterAction::FUNC_REGEX, '/<title[^>]*>(.*?)<\/title>/is');
        $result = $fetcher->applyAction($html['html'], $filter);
        $this->assertEquals('PHP - Wikipedia', $result[0][0]);
    }

    public function test_json(): void
    {
        $exceptionHappened = false;
        try {
            $filter = new FilterAction(FilterAction::SELECT, FilterAction::FUNC_JSON, 'title');
            $fetcher = new WebFetcher();
            $html = TestHelper::fetchSaveOrLoad("https://jsonplaceholder.typicode.com/posts");
            $result = $fetcher->applyAction($html['html'], $filter);
        } catch (\Exception $e) {
            $exceptionHappened = true;
            $this->assertEquals('JSON not supported yet.', $e->getMessage());
        }
        $this->assertTrue($exceptionHappened);
    }

    public function test_get_regex_overall_result(): void
    {
        $fetcher = new WebFetcher();
        $html = TestHelper::fetchSaveOrLoad("https://en.wikipedia.org/wiki/PHP");
        $filter = new FilterAction(FilterAction::SELECT, FilterAction::FUNC_REGEX, '/<title[^>]*>(.*?)<\/title>/is');
        $result = $fetcher->applyAction($html['html'], $filter);
        $this->assertEquals('PHP - Wikipedia', $result[0][0]);
    }

    public function test_get_regex_captures_as_array(): void
    {
        $fetcher = new WebFetcher();
        $html = TestHelper::fetchSaveOrLoad("https://jsonplaceholder.typicode.com/posts");
        $filter = new FilterAction(FilterAction::SELECT, FilterAction::FUNC_REGEX, '/(\"[a-zA-Z]+\")\s?:\s?(\"?[a-zA-Z0-9\s]+\"?)/');
        $result = $fetcher->applyAction($html['html'], $filter);
        $result = array_merge($result[0], $result[1]);
        $this->assertEquals(800, count($result));
        $this->assertEquals('"userId"', $result[0]);
        $this->assertEquals("1", $result[400]);
    }

    public function test_multifilter(): void
    {
        $mock = $this->createMock(WebFetcher::class);
        $mock->method('fetchWebsite')->willReturnCallback(fn($url) => TestHelper::fetchSaveOrLoad($url));

        $filters = [
            [FilterAction::FUNC_XPATH, '//*[@id="mw-content-text"]/div[1]/table[1]'],
            [FilterAction::FUNC_REGEX, '/<a\s+href="[^"]+"\s+title="([^"]+)">[^<]+<\/a>/']
        ];
        foreach($filters as &$filter) {
            $filter = new FilterAction(FilterAction::SELECT, $filter[0], $filter[1]);
        }
        $filters[] = new FilterAction(FilterAction::FILTER_IF_NOT_MATCH, FilterAction::FUNC_REGEX, '/.+mus.+/');

        $fetcher = new WebFetcher();
        $response = $mock->fetchWebsite("https://en.wikipedia.org/wiki/PHP");
        $this->assertEquals(200, $response['code']);
        $result = $fetcher->multiFilter($response['html'], $filters);
        $this->assertEquals('Rasmus Lerdorf', $result[0]);
    }
}
