<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Mailchimp\Mapper;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Mapper\MailchimpCreateContactMapper;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class MailchimpCreateContactMapperTest
 *
 * @package Tests\Integration\Model\Application\Impl\Mailchimp\Connector
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
        $app = self::$container->get('hbpf.application.mailchimp');

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'fa830d8d4308625bac307906e83de659'
        );

        $applicationInstall->setSettings(
            [
                MailchimpApplication::AUDIENCE_ID  => 'c9e7f10c5b',
                MailchimpApplication::API_KEYPOINT => $app->getApiEndpoint($applicationInstall),
            ]
        );

        $this->pf($applicationInstall);

        $dto = DataProvider::getProcessDto(
            $app->getKey(),
            'user',
            (string) file_get_contents(__DIR__ . sprintf('/Data/responseHubspot.json'), TRUE)
        );

        $mailchimpCreateContactMapper = new MailchimpCreateContactMapper();
        $dto                          = $mailchimpCreateContactMapper->process($dto);

        self::assertEquals(
            json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR),
            json_decode(
                (string) file_get_contents(
                    sprintf('%s/Data/requestMailchimp.json', __DIR__),
                    TRUE
                ),
                TRUE,
                512,
                JSON_THROW_ON_ERROR
            )
        );
    }

}

