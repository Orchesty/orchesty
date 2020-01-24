<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Joiner;

use Exception;
use Hanaboso\PipesPhpSdk\Joiner\Impl\NullJoiner;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use PipesPhpSdkTests\Integration\Application\TestNullApplication;

/**
 * Class JoinerAbstractTest
 *
 * @package PipesPhpSdkTests\Unit\Joiner
 */
final class JoinerAbstractTest extends DatabaseTestCaseAbstract
{

    /**
     * @var NullJoiner
     */
    private NullJoiner $joiner;

    /**
     * @covers \Hanaboso\PipesPhpSdk\Joiner\Impl\NullJoiner::save
     * @covers \Hanaboso\PipesPhpSdk\Joiner\Impl\NullJoiner::runCallback
     * @covers \Hanaboso\PipesPhpSdk\Joiner\Impl\NullJoiner::isDataComplete
     * @covers \Hanaboso\PipesPhpSdk\Joiner\JoinerAbstract::process
     */
    public function testProcess(): void
    {
        $result = $this->joiner->process(['data'], 2);

        self::assertEquals([], $result);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Joiner\JoinerAbstract::setApplication
     * @covers \Hanaboso\PipesPhpSdk\Joiner\JoinerAbstract::getApplicationKey
     */
    public function testGetApplicationKey(): void
    {
        $key = $this->joiner->getApplicationKey();
        self::assertNull($key);
        $key = $this->joiner->setApplication(new TestNullApplication())->getApplicationKey();

        self::assertEquals('null-key', $key);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->joiner = new NullJoiner();
    }

}
