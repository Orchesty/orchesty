<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/9/17
 * Time: 10:07 AM
 */

namespace Tests\Unit\TopologyGenerator;

use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;
use PHPUnit\Framework\TestCase;

/**
 * Class GeneratorUtilsTest
 *
 * @package Tests\Unit\TopologyGenerator
 */
class GeneratorUtilsTest extends TestCase
{

    /**
     * @covers GeneratorUtils::normalizeName()
     */
    public function testNormalizeName(): void
    {
        $this->assertEquals('123-test-name', GeneratorUtils::normalizeName('123', 'test.name'));
        $this->assertEquals('123-test-name', GeneratorUtils::normalizeName('123', 'test_name'));
    }

    /**
     * @covers GeneratorUtils::normalizeName()
     */
    public function testDenormalizeName(): void
    {
        $this->assertEquals('123', GeneratorUtils::denormalizeName('123-test-name'));
        $this->assertEquals('123', GeneratorUtils::denormalizeName('123-test'));
    }

}