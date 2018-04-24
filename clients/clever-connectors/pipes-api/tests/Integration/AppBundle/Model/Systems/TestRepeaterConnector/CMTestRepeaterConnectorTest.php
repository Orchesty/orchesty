<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 7.11.17
 * Time: 14:25
 */

namespace Tests\Integration\AppBundle\Model\Systems\TestRepeaterConnector;

use CleverConnectors\AppBundle\Model\CM\TestRepeaterConnector\CMTestRepeaterConnector;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class CMTestRepeaterConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\TestRepeaterConnector
 */
final class CMTestRepeaterConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $reddis = $this->container->get('snc_redis.default');
        $conn   = new CMTestRepeaterConnector($reddis);

        $dto = new ProcessDto();
        $dto->addHeader(CMHeaders::createKey(CMHeaders::PROCESS_ID), uniqid());

        do {
            $dto = $conn->processAction($dto);
        } while ($dto->getHeader(CMHeaders::createKey(CMHeaders::RESULT_CODE)) != '0');
    }

}