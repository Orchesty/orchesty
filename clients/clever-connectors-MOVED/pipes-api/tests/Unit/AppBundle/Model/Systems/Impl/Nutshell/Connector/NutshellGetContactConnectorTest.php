<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Connector\NutshellGetContactConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\NutshellSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class NutshellGetContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Connector
 */
final class NutshellGetContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $result = Json::decode($this->getConnectorMock()->processAction(
            (new ProcessDto())->setData(Json::encode([
                CleverFieldsEnum::FOREIGN_ID => 1,
            ]))->setHeaders([])
        )->getData(), TRUE);

        $this->assertEquals([
            'result'      => [
                'id'                => 1,
                'entityType'        => 'Contacts',
                'rev'               => '0',
                'modifiedTime'      => '2017-10-19T11:24:38+0000',
                'createdTime'       => '2017-10-18T14:16:54+0000',
                'name'              => [
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
                'email'             => [
                    1           => 'User01@User01.com',
                    '--primary' => 'User01@User01.com',
                ],
            ],
            'id'          => 'id',
            'error'       => NULL,
            'jsonrpc'     => '2.0',
            '_foreign_id' => 1,
        ], $result);
    }

    /**
     * @return NutshellGetContactConnector
     */
    private function getConnectorMock(): NutshellGetContactConnector
    {
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('getSystemInstall')->willReturn((new SystemInstall()));

        /** @var MockObject|DocumentManager $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstall);

        /** @var CurlManagerInterface|MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager->method('send')
            ->will($this->returnCallback(function (RequestDto $dto, array $options = []) {
                $this->assertEquals(
                    new Uri('https://app.nutshell.com/api/v1/json'),
                    $dto->getUri()
                );

                return new ResponseDto(200, 'OK', $this->getRequest('NutshellSingleContactItem.json'), []);
            }));

        return new NutshellGetContactConnector($this->getSystemMock(), $documentManager, $curlManager);
    }

    /**
     * @return MockObject|NutshellSystem
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