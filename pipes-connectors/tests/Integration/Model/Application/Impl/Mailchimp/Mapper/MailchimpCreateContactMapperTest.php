<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Mailchimp\Mapper;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Mapper\MailchimpCreateContactMapper;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\MockCurlMethod;

/**
 * Class MailchimpCreateContactMapperTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Mailchimp\Mapper
 */
final class MailchimpCreateContactMapperTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    200,
                    'responseDatacenter.json',
                    [],
                ),
            ],
        );

        $app = self::getContainer()->get('hbpf.application.mailchimp');

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getName(),
            'user',
            'fa830d8d43*****bac307906e83de659',
        );

        $applicationInstall->setSettings(
            [
                MailchimpApplication::AUDIENCE_ID  => '2a8******8',
                MailchimpApplication::API_KEYPOINT => $app->getApiEndpoint($applicationInstall),
            ],
        );

        $this->pfd($applicationInstall);

        $dto = DataProvider::getProcessDto(
            $app->getName(),
            'user',
            File::getContent(__DIR__ . '/Data/responseHubspot.json'),
        );

        $mailchimpCreateContactMapper = new MailchimpCreateContactMapper();
        $dto                          = $mailchimpCreateContactMapper->processAction($dto);
        $dtoNoBody                    = $mailchimpCreateContactMapper->processAction($dto);

        self::assertEquals(
            Json::decode($dto->getData()),
            Json::decode(File::getContent(sprintf('%s/Data/requestMailchimp.json', __DIR__))),
        );
        self::assertEquals(
            Json::decode($dtoNoBody->getData()),
            Json::decode(File::getContent(sprintf('%s/Data/requestMailchimp.json', __DIR__))),
        );

        self::assertEquals(ProcessDtoAbstract::STOP_AND_FAILED, $dtoNoBody->getHeaders()['result-code']);
    }

}
