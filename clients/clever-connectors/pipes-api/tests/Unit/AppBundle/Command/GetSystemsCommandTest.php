<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/3/17
 * Time: 11:03 AM
 */

namespace Tests\Unit\AppBundle\Command;

use CleverConnectors\AppBundle\Command\GetSystemsCommand;
use Doctrine\MongoDB\Cursor;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use MongoDB\Collection;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class GetSystemsCommandTest
 *
 * @package Unit\AppBundle\Command
 */
final class GetSystemsCommandTest extends TestCase
{

    /**
     *
     */
    public function testGetSystem(): void
    {
        $cursor = $this->createMock(Cursor::class);
        $cursor->method('toArray')->willReturn(['id' => '1']);

        $collection = $this->createMock(Collection::class);
        $collection->method('find')->willReturn($cursor);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getDocumentCollection')->willReturn($collection);

        $cmd = new GetSystemsCommand($dm);

        $tester = new CommandTester($cmd);
        $tester->execute([
            'system-key' => 'test',
        ]);

        $output = $tester->getDisplay();
        $this->assertSame('{"id":"1"}', trim($output));
    }

    /**
     *
     */
    public function testGetSystemException(): void
    {
        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getDocumentCollection')->willThrowException(new Exception());

        $cmd = new GetSystemsCommand($dm);

        $tester = new CommandTester($cmd);
        $tester->execute([
            'system-key' => 'test',
        ]);

        $output = $tester->getDisplay();
        $this->assertSame('', trim($output));
    }

}