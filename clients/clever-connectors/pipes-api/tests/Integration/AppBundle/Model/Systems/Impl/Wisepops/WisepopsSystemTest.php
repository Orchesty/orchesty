<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Wisepops;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class WisepopsSystemTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Wisepops
 */
final class WisepopsSystemTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testRunSaveCustomForm(): void
    {
        $systemInstall = (new SystemInstall())
            ->setSystem('wisepops')
            ->setUser('User')
            ->setToken('Token');
        $this->persistAndFlush($systemInstall);

        $manager = $this->ownContainer->get('cc.systems.manager');
        $manager->runCustomAction('wisepops', 'User', 'saveCustomForm', ['key' => 'value']);

        $this->dm->clear();
        $systemInstall = $this->dm->getRepository(SystemInstall::class)->find($systemInstall->getId());

        $this->assertEquals([
            'custom_form' => [
                'key' => 'value',
            ],
        ], $systemInstall->getSettings());
    }

}