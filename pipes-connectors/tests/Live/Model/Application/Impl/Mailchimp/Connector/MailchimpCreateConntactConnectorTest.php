<?php declare(strict_types=1);

namespace Tests\Live\Model\Application\Impl\Mailchimp\Connector;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector\MailchimpCreateContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Exception\ApplicationInstallException;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class MailchimpCreateConntactConnectorTest
 *
 * @package Tests\Live\Model\Application\Impl\Mailchimp\Connector
 */
final class MailchimpCreateConntactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws DateTimeException
     * @throws PipesFrameworkException
     * @throws CurlException
     * @throws ApplicationInstallException
     */
    public function testProcessAction(): void
    {
        $app                             = self::$container->get('hbpf.application.mailchimp');
        $mailchimpCreateContactConnector = new MailchimpCreateContactConnector(
            $app,
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'fa830d8d4308625bac307906e83de659'
        );

        $applicationInstall->setSettings([
            MailchimpApplication::AUDIENCE_ID => 'c9e7f10c5b',
        ]);

        $applicationInstall->setSettings([
            OAuth2ApplicationInterface::API_KEYPOINT => $app->getApiEndpoint($applicationInstall),
        ]);
        $this->pf($applicationInstall);
        $this->dm->clear();

        $data = '{
                                "email_address": "urt.m@freddiesjokes.com",
                                "status": "subscribed",
                                "merge_fields": {
                                    "FNAME": "U",
                                    "LNAME": "M" }
                              }';
        $mailchimpCreateContactConnector->processAction(DataProvider::getProcessDto(
            $app->getKey(),
            'user',
            $data
        ));
    }

}
