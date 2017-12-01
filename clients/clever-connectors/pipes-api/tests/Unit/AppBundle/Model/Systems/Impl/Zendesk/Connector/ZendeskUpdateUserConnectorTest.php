<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector\ZendeskUpdateUserConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZendeskUpdateUserConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
final class ZendeskUpdateUserConnectorTest extends ConnectorTestCaseAbstract
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
        $conn       = new ZendeskUpdateUserConnector(
            $this->container->get('systems.zendesk'),
            $this->mockDm(),
            $this->mockCurl()
        );

        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE,
        ])->setData(json_encode([
            'body' => json_encode([
                'user' => [
                    'user_fields' => [
                        CleverCustomKeysEnum::UNSUBSCRIBE => FALSE,
                    ],
                ],
            ]),
            'id'   => '123456',
        ]));

        $conn->processAction($dto);
    }

    /**
     * @return DocumentManager|MockObject
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
     * @return CurlManagerInterface|MockObject
     */
    private function mockCurl(): CurlManagerInterface
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(function (RequestDto $requestDto) {
                $expt = new RequestDto('PUT', new Uri('https://hbpf.zendesk.com/api/v2/users/123456.json'));
                $expt->setHeaders([
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => 'Basic ' . $this->auth,
                ])->setBody(json_encode([
                    'user' => [
                        'user_fields' => [
                            CleverCustomKeysEnum::UNSUBSCRIBE => FALSE,
                        ],
                    ],
                ]));

                self::assertEquals($expt, $requestDto);

                return new ResponseDto(200, '', $this->getRequest('updateUser.json'), []);
            }));

        return $curl;
    }

}