<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class PipedriveGetLimitConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
final class PipedriveGetLimitConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $this->markTestSkipped();

        $connector  = $this->container->get('hbpf.connector.pipedrive-get-limit-connector');
        $processDto = $connector->processAction(
            $this->prepareConnectorProcessDto(
                [
                    'api_token' => '0d15d3c7a9fd00710cbfdab2b87dadbf2173ae3d',
                ]
            )
        );

        $system = $this->container->get('systems.pipedrive');
        $sys    = $this->dm->getRepository(SystemInstall::class)
            ->getSystemInstallFromHeaders($processDto->getHeaders());

        /** @var SystemLimitDto $limits */
        $limits = $system->getLimit($sys);

        self::assertEquals(200, $limits->getLimitValue());
        self::assertEquals(10, $limits->getLimitTime());
    }

}