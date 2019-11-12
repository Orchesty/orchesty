<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Mailchimp\Connector;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector\MailchimpCreateContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;
use Tests\MockCurlMethod;

/**
 * Class MailchimpCreateContactConnectorTest
 *
 * @package Tests\Integration\Model\Application\Impl\Mailchimp\Connector
 */
final class MailchimpCreateContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @param int  $code
     * @param bool $isValid
     *
     * @throws DateTimeException
     * @throws PipesFrameworkException
     * @throws CurlException
     * @throws ApplicationInstallException
     *
     * @dataProvider getDataProvider
     */
    public function testProcessAction(int $code, bool $isValid): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    $code,
                    'responseDatacenter.json',
                    []
                ),
                new MockCurlMethod(
                    $code,
                    sprintf('response%s.json', $code),
                    []
                ),
            ]
        );

        $app                             = self::$container->get('hbpf.application.mailchimp');
        $mailchimpCreateContactConnector = new MailchimpCreateContactConnector(
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );

        $mailchimpCreateContactConnector->setApplication($app);

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'fa830d8d4308625bac307906e83de659'
        );

        $applicationInstall->setSettings(
            [
                ApplicationAbstract::FORM          => [
                    MailchimpApplication::AUDIENCE_ID => 'c9e7f10c5b',
                ],
                MailchimpApplication::API_KEYPOINT => $app->getApiEndpoint($applicationInstall),

            ]
        );

        $this->pf($applicationInstall);

        $dto      = DataProvider::getProcessDto(
            $app->getKey(),
            'user',
            (string) file_get_contents(__DIR__ . sprintf('/Data/response%s.json', $code), TRUE)
        );
        $response = $mailchimpCreateContactConnector->processAction($dto);

        if ($isValid) {
            self::assertSuccessProcessResponse(
                $response,
                sprintf('response%s.json', $code)
            );
        } else {
            self::assertFailedProcessResponse(
                $response,
                sprintf('response%s.json', $code)
            );
        }
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [400, FALSE],
            [200, TRUE],
        ];
    }

}

