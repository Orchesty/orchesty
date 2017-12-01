<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Connector\BasecrmCreateContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BasecrmCreateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Connector
 */
final class BasecrmCreateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testCreateContact(): void
    {
        $data = [
            'data' => [
                'first_name'    => 'first',
                'last_name'     => 'last',
                'email'         => 'eml@eml.com',
                'custom_fields' => [
                    'cm_hard_bounce' => FALSE,
                    'cm_unsubscribe' => FALSE,
                ],
            ],
        ];

        $conn = new BasecrmCreateContactConnector(
            $this->container->get('systems.basecrm'),
            $this->mockDm(),
            $this->mockCurl($data)
        );
        $dto  = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode($data));

        $conn->processAction($dto);
    }

    /**
     * @param array $data
     * @param int   $status
     *
     * @return CurlManagerInterface|MockObject
     */
    private function mockCurl(array $data, int $status = 200): CurlManagerInterface
    {
        $test = $this;
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(function (RequestDto $dto) use ($test, $status, $data) {
                $expt = new RequestDto('POST', new Uri('https://api.getbase.com/v2/contacts'));
                $expt->setHeaders([
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                    'User-Agent'    => 'Chrome/58.0.3029.96 Safari/537.36',
                    'Authorization' => 'Bearer acctoken',
                ])->setBody(json_encode($data));

                $test->assertEquals($expt, $dto);

                return new ResponseDto($status, '', '', []);
            }));

        return $curl;
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings(['access_token' => 'acctoken']);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        return $dm;
    }

}