<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Connector\NutshellCreateContactConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\NutshellSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Nette\Utils\Json;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class NutshellCreateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Connector
 */
final class NutshellCreateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $result = Json::decode($this->getConnectorMock()->processAction(
            (new ProcessDto())->setData(Json::encode([
                'jsonrpc' => '2.0',
                'id'      => 'contact',
                'method'  => 'newContact',
                'params'  => [
                    'contact' => [
                        'name'  => [
                            'givenName'  => 'User01',
                            'familyName' => 'User01',
                        ],
                        'email' => ['User01@User01.com'],
                    ],
                ],
            ]))->setHeaders([])
        )->getData(), TRUE);

        $this->assertEquals([
            'result'  =>
                [
                    'id'                => 1,
                    'entityType'        => 'Contacts',
                    'rev'               => '0',
                    'modifiedTime'      => '2017-10-19T11:24:38+0000',
                    'createdTime'       => '2017-10-18T14:16:54+0000',
                    'name'              =>
                        [
                            'givenName'   => 'User01',
                            'familyName'  => 'User01',
                            'salutation'  => '',
                            'displayName' => 'User01 User01',
                        ],
                    'htmlUrl'           => 'https://app.nutshell.com/person/1-user01-user01',
                    'creator'           => NULL,
                    'owner'             => NULL,
                    'leads'             => [],
                    'accounts'          => [],
                    'notes'             => [],
                    'lastContactedDate' => NULL,
                    'contactedCount'    => 0,
                    'tags'              => [],
                    'description'       => NULL,
                    'email'             =>
                        [
                            1           => 'User01@User01.com',
                            '--primary' => 'User01@User01.com',
                        ],
                ],
            'id'      => 'id',
            'error'   => NULL,
            'jsonrpc' => '2.0',
        ], $result);
    }

    /**
     * @return NutshellCreateContactConnector
     */
    private function getConnectorMock(): NutshellCreateContactConnector
    {
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('getSystemInstall')->willReturn((new SystemInstall()));

        /** @var PHPUnit_Framework_MockObject_MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstall);

        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager->method('send')
            ->will($this->returnCallback(function (RequestDto $dto, array $options = []) {
                $this->assertEquals(
                    new Uri('https://app.nutshell.com/api/v1/json'),
                    $dto->getUri()
                );

                return new ResponseDto(200, 'OK', $this->getRequest('NutshellSingleContactItem.json'), []);
            }));

        return new NutshellCreateContactConnector($this->getSystemMock(), $documentManager, $curlManager);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|NutshellSystem
     */
    private function getSystemMock()
    {
        $requestDto = (new RequestDto('POST', new Uri('https://app.nutshell.com/api/v1/json')))->setHeaders([
            'Authorization' => 'Basic bnV0c2hlbGxAbWFpbGluYXRvci5jb206OTY3YjFmN2IzMjFlNjMwNWQxOGU2NjU2YTY1MGMzMjQyMGFiYTk4ZA==',
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ]);

        $system = $this->createMock(NutshellSystem::class);
        $system->method('getRequestDto')->willReturn($requestDto);

        return $system;
    }

}