<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Nutshell;

use Exception;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class NutshellApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Nutshell
 */
final class NutshellApplicationTest extends DatabaseTestCaseAbstract
{

    public const USER    = 'user@user.com';
    public const API_KEY = '271cca5c67c**********427b659988cc38e2f78';


    /**
     * @throws Exception
     */
    public function testAuthorization(): void
    {
        $nutshellApplication = self::$container->get('hbpf.application.nutshell');
        $curl                = self::$container->get('hbpf.transport.curl_manager');
        $applicationInstall  = DataProvider::getBasicAppInstall(
            $nutshellApplication->getKey(),
            self::USER,
            self::API_KEY
        );

        $this->pf($applicationInstall);

        $result = $curl->send(
            $nutshellApplication->getRequestDto(
                $applicationInstall,
                'POST',
                'http://api.nutshell.com/v1/json',
                '{"id": "apeye", "method": "getLead", "params": { "leadId": 1000 }, "data":{"username": "user@user.com"} }'
            )
        );

        $this->assertEquals(200, $result->getStatusCode());
    }

}
