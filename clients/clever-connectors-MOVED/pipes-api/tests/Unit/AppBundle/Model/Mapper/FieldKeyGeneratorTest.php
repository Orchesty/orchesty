<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 23.11.17
 * Time: 15:35
 */

namespace Tests\Unit\AppBundle\Model\Mapper;

use CleverConnectors\AppBundle\Model\Mapper\FieldKeyGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Class FieldKeyGeneratorTest
 *
 * @package Tests\Unit\AppBundle\Model\Mapper
 */
final class FieldKeyGeneratorTest extends TestCase
{

    /**
     *
     */
    public function testParseKey(): void
    {
        $emptyKey = '';
        $key      = 'key';
        $innerKey = 'inner.key';

        $res = FieldKeyGenerator::parseKey($emptyKey);
        self::assertEmpty($res);

        $res = FieldKeyGenerator::parseKey($key);
        self::assertNotEmpty($res);
        self::assertEquals($key, reset($res));

        $res = FieldKeyGenerator::parseKey($innerKey);
        self::assertNotEmpty($res);
        self::assertEquals('inner', reset($res));
        self::assertEquals('key', next($res));
    }

}