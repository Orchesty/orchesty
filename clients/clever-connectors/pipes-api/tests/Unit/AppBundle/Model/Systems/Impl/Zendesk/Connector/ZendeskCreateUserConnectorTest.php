<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector\ZendeskCreateUserConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZendeskCreateUserConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
final class ZendeskCreateUserConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @var string
     */
    private $auth;

    /**
     *
     */
    public function testConnector(): void
    {
        $this->auth = base64_encode('eml@eml.com/token:smToken');
        $conn       = new ZendeskCreateUserConnector(
            $this->container->get('systems.zendesk'),
            $this->mockDm(),
            $this->mockCurl()
        );

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode([
            'user' => [
                'email' => 'eml@eml.com',
                'name'  => 'first last',
            ],
        ]));

        $conn->processAction($dto);
    }

    /**
     * @return DocumentManager|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            'user_email' => 'eml@eml.com',
            'api_token'  => 'smToken',
            'domain'     => 'hbpf',
        ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        return $dm;
    }

    /**
     * @return CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCurl(): CurlManagerInterface
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(function (RequestDto $requestDto) {
                $expt = new RequestDto('POST', new Uri('https://hbpf.zendesk.com/api/v2/users.json'));
                $expt->setHeaders([
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => 'Basic ' . $this->auth,
                ])->setBody(json_encode([
                    'user' => [
                        'email' => 'eml@eml.com',
                        'name'  => 'first last',
                    ],
                ]));

                self::assertEquals($expt, $requestDto);

                return new ResponseDto(201, '', '', []);
            }));

        return $curl;
    }

}