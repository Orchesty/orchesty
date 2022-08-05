<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector;

use Exception;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\Connector\FakturoidAbstractConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Fakturoid\FakturoidApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;

/**
 * Class FakturoidAbstractConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Fakturoid\Connector
 */
abstract class FakturoidAbstractConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    abstract protected function testGetKey(): void;

    /**
     * @param ResponseDto    $dto
     * @param Exception|null $exception
     *
     * @return FakturoidAbstractConnector
     */
    abstract protected function createConnector(
        ResponseDto $dto,
        ?Exception $exception = NULL,
    ): FakturoidAbstractConnector;

    /**
     * @return FakturoidAbstractConnector
     */
    abstract protected function setApplication(): FakturoidAbstractConnector;

    /**
     * @param string|null $account
     *
     * @return FakturoidAbstractConnector
     * @throws Exception
     */
    public function setApplicationAndMock(?string $account = NULL): FakturoidAbstractConnector
    {
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    BasicApplicationInterface::USER     => 'hana******.com',
                    BasicApplicationInterface::PASSWORD => 'cf4*****191bbef40dcd86*****625ec4c4*****',
                    FakturoidApplication::ACCOUNT       => $account,
                ],
            ],
        );

        $applicationInstall->setUser('user');
        $applicationInstall->setKey('fakturoid');
        $this->pfd($applicationInstall);
        $this->dm->clear();

        return $this->setApplication();
    }

    /**
     * @param string|null $account
     *
     * @return FakturoidAbstractConnector
     * @throws Exception
     */
    public function setApplicationAndMockWithoutHeader(?string $account = NULL): FakturoidAbstractConnector
    {
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    BasicApplicationInterface::USER => 'hana******.com',
                    FakturoidApplication::ACCOUNT   => $account,
                ],
            ],
        );

        $applicationInstall->setUser('user');
        $applicationInstall->setKey('fakturoid');
        $this->pfd($applicationInstall);
        $this->dm->clear();

        return $this->setApplication();
    }

}
