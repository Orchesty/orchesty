<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\Mailchimp;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class MailchimpApplicationTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\Mailchimp
 */
final class MailchimpApplicationTest extends KernelTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     * @throws Exception
     */
    public function testAutorize(): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $app = self::getContainer()->get('hbpf.application.mailchimp');

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getName(),
            'user',
            'token123',
            '6748****7235',
            'f8fe8943e9b258b46d7220a5**********b67bd5178b71f738',
        );
        $applicationInstall = $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM =>
                    [
                        ApplicationInterface::FRONTEND_REDIRECT_URL => 'xxxx',
                    ],
                MailchimpApplication::AUDIENCE_ID                 => 'c9e7f***5b',
            ],
        );

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["flexibee"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode($applicationInstall->toArray())),
            ),
        );
        $app->authorize($applicationInstall);
        self::assertFake();
    }

}
