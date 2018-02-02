<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Filter;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Filter\FacebookaudienceGetListSubscribersFilter;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookaudienceGetListSubscribersFilterTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Filter
 */
final class FacebookaudienceGetListSubscribersFilterTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers FacebookaudienceGetSubscribersFilter::process()
     */
    public function testProcess(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            SystemInstall::DISTRIBUTION_LIST => 'all',
        ]);

        $systemInstallRepository = $this->createMock(SystemInstallRepository::class);
        $systemInstallRepository->method('getSystemInstallFromHeaders')->willReturn($systemInstall);

        /** @var MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstallRepository);

        $conn = new FacebookaudienceGetListSubscribersFilter($documentManager);
        $dto  = (new ProcessDto())->setData('')->setHeaders([]);

        $result = $conn->process($dto);

        $this->assertEmpty($result->getData());
        $this->assertEquals([
            'pf-result-code' => 1003,
        ], $result->getHeaders());
    }

    /**
     * @covers FacebookaudienceGetListSubscribersFilter::process()
     */
    public function testProcess2(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            SystemInstall::DISTRIBUTION_LIST => '123',
        ]);

        $systemInstallRepository = $this->createMock(SystemInstallRepository::class);
        $systemInstallRepository->method('getSystemInstallFromHeaders')->willReturn($systemInstall);

        /** @var MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstallRepository);

        $conn = new FacebookaudienceGetListSubscribersFilter($documentManager);
        $dto  = (new ProcessDto())->setData('')->setHeaders([]);

        $result = $conn->process($dto);

        $this->assertEmpty($result->getData());
        $this->assertEmpty($result->getHeaders());
    }

}