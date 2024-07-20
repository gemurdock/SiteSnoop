<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\JSONQuery;
use App\Services\QueryResultNotFoundException;

class JSONQueryTest extends TestCase
{
    // Field Tests //

    public function test_find_single_field(): void
    {
        $jsonString = '{"name": "John", "age": 30, "city": "New York"}';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$.name');
        $this->assertEquals('John', $result);
        $result = $data->query('$.city');
        $this->assertEquals('New York', $result);
    }

    public function test_find_a_number(): void
    {
        $jsonString = '{"name": "John", "age": 30, "city": "New York"}';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$.age');
        $this->assertEquals(30, $result);
    }

    public function test_field_starts_with_dot(): void
    {
        $jsonString = '{".name": "John", "age": 30, "city": "New York"}';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$.\.name');
        $this->assertEquals('John', $result);
    }

    public function test_field_contains_dot(): void
    {
        $jsonString = '{"name.first": "John", "age": 30, "city": "New York"}';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$.name\.first');
        $this->assertEquals('John', $result);
    }

    public function test_field_contains_dot_at_end(): void
    {
        $jsonString = '{"namefirst.": "John", "age": 30, "city": "New York"}';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$.namefirst\.');
        $this->assertEquals('John', $result);
    }

    public function test_field_contains_dot_start_middle_end(): void
    {
        $jsonString = '{".name.first.": "John", "age": 30, "city": "New York"}';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$.\.name\.first\.');
        $this->assertEquals('John', $result);
    }

    // Array Tests //

    public function test_array_first(): void
    {
        $jsonString = '["John", "Doe", "New York"]';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$[0]');
        $this->assertEquals('John', $result);
    }

    public function test_array_last(): void
    {
        $jsonString = '["John", "Doe", "New York"]';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$[2]');
        $this->assertEquals('New York', $result);
    }

    public function test_array_subarray(): void
    {
        $jsonString = '["John", ["Doe", "Jane"], "New York"]';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$[1][0]');
        $this->assertEquals('Doe', $result);
    }

    // Nested Tests //

    public function test_array_in_object(): void
    {
        $jsonString = '{"name": "John", "children": ["Doe", "Jane"], "city": "New York"}';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$.children[0]');
        $this->assertEquals('Doe', $result);
    }

    public function test_nested_field(): void
    {
        $jsonString = '{"name": {"first": "John", "last": "Doe"}, "age": 30, "city": "New York"}';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$.name.first');
        $this->assertEquals('John', $result);
    }

    public function test_multiple_arrays_and_objects(): void
    {
        $jsonString = '{"name": {"first": "John", "last": "Doe"}, "children": ["Doe", "Jane"], "city": "New York"}';
        $data = new JSONQuery($jsonString);

        $result = $data->query('$.children[0]');
        $this->assertEquals('Doe', $result);

        $result = $data->query('$.name.first');
        $this->assertEquals('John', $result);

        $result = $data->query('$.city');
        $this->assertEquals('New York', $result);
    }

    public function test_deep_nested_search(): void
    {
        $jsonString = '{"a": {"b": {"c": {"d": {"e": ["a", "b", "c", "d", "e", "f"]}}}}}';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$.a.b.c.d.e[5]');
        $this->assertEquals('f', $result);
    }

    public function test_deep_nested_array_search(): void
    {
        $jsonString = '[[[[["a", "b", "c", "d", "e", "f"]]]]]';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$[0][0][0][0][5]');
        $this->assertEquals('f', $result);
    }

    // Exception Tests //

    public function test_array_negative_index(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array index must be a non-negative integer.');
        $jsonString = '["John", "Doe", "New York"]';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$[-1]');
    }

    public function test_array_string_in_index(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array index must be a number.');
        $jsonString = '["John", "Doe", "New York"]';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$["name"]');
    }

    public function test_invalid_json_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data must be valid JSON.');
        $data = new JSONQuery('{"name": "John"');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data must be valid JSON.');
        $data = new JSONQuery('{ "name": ["John", "Doe"');
    }

    public function test_invalid_query_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Query must start with root represented by \'$\'');
        $jsonString = '{"name": "John"}';
        $data = new JSONQuery($jsonString);
        $result = $data->query('.name');

        $this->expectExceptionMessage('Query must be a non-empty string.');
        $jsonString = '{"name": "John"}';
        $data = new JSONQuery($jsonString);
        $result = $data->query('');
    }

    public function test_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data must be a non-empty string.');
        $data = new JSONQuery('');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Query must be a non-empty string.');
        $data = new JSONQuery('{".name": "John", "age": 30, "city": "New York"}');
        $result = $data->query('');
    }

    public function test_field_not_found_throw_exception(): void
    {
        $this->expectException(QueryResultNotFoundException::class);
        $this->expectExceptionMessage('Query result not found.');
        $jsonString = '{"name": "John"}';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$.city');
    }

    public function test_array_index_not_found_throw_exception(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $this->expectExceptionMessage('Array index out of bounds.');
        $jsonString = '["John", "Doe"]';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$[2]');
    }

    public function test_array_index_not_found_in_subarray_throw_exception(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $this->expectExceptionMessage('Array index out of bounds.');
        $jsonString = '["John", ["Doe", "Jane"], "New York"]';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$[1][2]');
    }

    public function test_null_data_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data must be a non-empty string.');
        $data = new JSONQuery(null);
    }

    // Misc tests //

    public function test_null_is_not_interpreted_as_string(): void
    {
        $testString = '{"name": null}';
        $data = new JSONQuery($testString);
        $result = $data->query('$.name');
        $this->assertEquals(null, $result);
        $this->assertIsNotString($result);
    }

    public function test_boolean_is_read_as_boolean(): void
    {
        $testString = '{"doAct": true}';
        $data = new JSONQuery($testString);
        $result = $data->query('$.doAct');
        $this->assertEquals(true, $result);
    }

    public function test_root_only_returns_no_changes(): void
    {
        $jsonString = '{ "name": "John" }';
        $data = new JSONQuery($jsonString);
        $result = $data->query('$', false);
        $this->assertEquals(str_replace(' ', '', $jsonString), $result);
    }
}
