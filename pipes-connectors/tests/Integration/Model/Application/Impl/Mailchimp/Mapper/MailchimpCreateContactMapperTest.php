<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Mailchimp\Mapper;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Mapper\MailchimpCreateContactMapper;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;
use Tests\MockCurlMethod;

/**
 * Class MailchimpCreateContactMapperTest
 *
 * @package Tests\Integration\Model\Application\Impl\Mailchimp\Mapper
 */
final class MailchimpCreateContactMapperTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws PipesFrameworkException
     */
    public function testProcessAction(): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    200,
                    'responseDatacenter.json',
                    []
                ),
            ]
        );

        $app = self::$container->get('hbpf.application.mailchimp');

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'fa830d8d43*****bac307906e83de659'
        );

        $applicationInstall->setSettings(
            [
                MailchimpApplication::AUDIENCE_ID  => '2a8******8',
                MailchimpApplication::API_KEYPOINT => $app->getApiEndpoint($applicationInstall),
            ]
        );

        $this->pf($applicationInstall);

        $dto = DataProvider::getProcessDto(
            $app->getKey(),
            'user',
            (string) file_get_contents(__DIR__ . sprintf('/Data/responseHubspot.json'), TRUE)
        );

        $dtoNoBody = DataProvider::getProcessDto(
            $app->getKey(),
            'user',
            '{}'
        );

        $mailchimpCreateContactMapper = new MailchimpCreateContactMapper();
        $dto                          = $mailchimpCreateContactMapper->process($dto);
        $dtoNoBody                    = $mailchimpCreateContactMapper->process($dto);

        self::assertEquals(
            Json::decode($dto->getData()),
            Json::decode(
                (string) file_get_contents(
                    sprintf('%s/Data/requestMailchimp.json', __DIR__),
                    TRUE
                )
            )
        );
        self::assertEquals(
            Json::decode($dtoNoBody->getData()),
            Json::decode(
                (string) file_get_contents(
                    sprintf('%s/Data/requestMailchimp.json', __DIR__),
                    TRUE
                )
            )
        );

        self::assertEquals($dtoNoBody->getHeaders()['pf-result-code'], ProcessDto::STOP_AND_FAILED);
    }

}
