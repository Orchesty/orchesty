<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 1/22/18
 * Time: 11:43 AM
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\QuickbooksSystem;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class QuickbooksSystemTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks
 */
class QuickbooksSystemTest extends TestCase
{

    /**
     * @var QuickbooksSystem
     */
    private $system;

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var MockObject|OAuth2Provider $provider */
        $provider = $this->createMock(OAuth2Provider::class);

        $this->system        = new QuickbooksSystem($provider);
        $this->systemInstall = (new SystemInstall())->setSystem($this->system->getKey());
    }

    /**
     *
     */
    public function testGetLimit(): void
    {
        $data = $this->system->getLimit($this->systemInstall)->toArray();
        unset($data['limit-last-update']);

        $this->assertEquals([
            'pf-limit-key'   => 'quickbooks',
            'pf-limit-value' => 500,
            'pf-limit-time'  => 60,
        ], $data);
    }

    /**
     *
     */
    public function testSaveLimit(): void
    {
        $this->assertInstanceOf(SystemInstall::class, $this->system->saveLimit($this->systemInstall, []));
    }

}