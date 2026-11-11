<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Utils;

use Hanaboso\PipesFramework\Utils\JsonSchemaArgsValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class JsonSchemaArgsValidatorTest
 *
 * @package PipesFrameworkTests\Unit\Utils
 */
#[CoversClass(JsonSchemaArgsValidator::class)]
final class JsonSchemaArgsValidatorTest extends TestCase
{

    private const array SCHEMA = [
        'additionalProperties' => FALSE,
        'properties'           => [
            'active' => ['type' => 'boolean'],
            'city'   => ['type' => 'string'],
            'count'  => ['type' => 'integer'],
            'mode'   => ['enum' => ['a', 'b'], 'type' => 'string'],
            'price'  => ['type' => 'number'],
            'tags'   => ['type' => 'array'],
        ],
        'required'             => ['city', 'count'],
        'type'                 => 'object',
    ];

    /**
     * @return void
     */
    public function testCoerce(): void
    {
        self::assertSame(
            [
                'active' => TRUE,
                'city'   => '12',
                'count'  => 3,
                'other'  => '1',
                'price'  => 1.5,
            ],
            JsonSchemaArgsValidator::coerce(
                self::SCHEMA,
                ['active' => 'true', 'city' => 12, 'count' => '3', 'other' => '1', 'price' => '1.5'],
            ),
        );
        self::assertSame(['a' => '1'], JsonSchemaArgsValidator::coerce([], ['a' => '1']));
        self::assertSame(
            ['active' => FALSE, 'city' => 'false', 'count' => 2, 'price' => 2],
            JsonSchemaArgsValidator::coerce(
                self::SCHEMA,
                ['active' => 0, 'city' => FALSE, 'count' => 2.0, 'price' => '2'],
            ),
        );
    }

    /**
     * @return void
     */
    public function testValidate(): void
    {
        self::assertNull(JsonSchemaArgsValidator::validate([], ['x' => 1]));
        self::assertNull(JsonSchemaArgsValidator::validate(self::SCHEMA, ['city' => 'Prague', 'count' => 1]));
        self::assertSame(
            'Missing required parameter "city".',
            JsonSchemaArgsValidator::validate(self::SCHEMA, ['count' => 1]),
        );
        self::assertSame(
            [
                'Missing required parameter "count".',
                'Parameter "active" must be of type boolean.',
                'Parameter "mode" must be one of: a, b.',
                'Parameter "tags" must be of type array.',
                'Unknown parameter "other".',
            ],
            JsonSchemaArgsValidator::errors(
                self::SCHEMA,
                ['active' => 'x', 'city' => 'Prague', 'mode' => 'c', 'tags' => ['k' => 1], 'other' => 1],
            ),
        );
    }

    /**
     * @return void
     */
    public function testValidateBounds(): void
    {
        $schema = [
            'properties' => [
                'code'  => ['pattern' => '^[A-Z]{2}/\d+$', 'type' => 'string'],
                'id'    => ['maximum' => 10, 'minimum' => 1, 'type' => 'integer'],
                'price' => ['maximum' => 9.5, 'minimum' => 0.5, 'type' => 'number'],
            ],
            'type'       => 'object',
        ];

        self::assertSame([], JsonSchemaArgsValidator::errors($schema, ['code' => 'CZ/12', 'id' => 1, 'price' => 9.5]));
        self::assertSame(
            [
                'Parameter "code" must match the pattern ^[A-Z]{2}/\d+$.',
                'Parameter "id" must be at least 1.',
                'Parameter "price" must be at most 9.5.',
            ],
            JsonSchemaArgsValidator::errors($schema, ['code' => 'cz-12', 'id' => 0, 'price' => 10]),
        );
        self::assertSame(
            'Parameter "id" must be at most 10.',
            JsonSchemaArgsValidator::validate($schema, JsonSchemaArgsValidator::coerce($schema, ['id' => '11'])),
        );
        self::assertNull(
            JsonSchemaArgsValidator::validate(
                ['properties' => ['code' => ['pattern' => '([', 'type' => 'string']]],
                ['code' => 'x'],
            ),
        );
    }

}
