<?php declare(strict_types=1);

namespace Tests\Controller\AppBundle\Controller;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use Tests\ControllerTestCaseAbstract;

/**
 * Class SystemControllerTest
 *
 * @package Tests\Integration\AppBundle\Controller
 */
class SystemControllerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    public function testGetSystem(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $response = $this->sendGet('/systems/null.user.group');
        $this->assertEquals(200, $response->status);
        $this->assertEquals((object) $this->getArrayDataForAssert(), $response->content);
    }

    /**
     *
     */
    public function testGetSystemNotFound(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $response = $this->sendGet('/systems/unknown');
        $this->assertEquals(500, $response->status);
    }

    /**
     *
     */
    public function testGetSystemsByUser(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $response = $this->sendGet('/systems', ['user' => 'someUser']);
        $this->assertEquals(200, $response->status);
        $this->assertEquals(
            [
                (object) $this->getArrayDataForAssert(),
                (object) $this->getArrayDataForAssert(),
            ],
            $response->content
        );
    }

    /**
     *
     */
    public function testGetSystemsByUserNotFound(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $response = $this->sendGet('/systems', ['user' => 'unknown']);
        $this->assertEquals(500, $response->status);
        $this->assertEquals(SystemException::class, $response->content->type);
        $this->assertEquals(SystemException::SYSTEM_PROPERTY_NOT_FOUND, $response->content->error_code);
    }

    /**
     *
     */
    public function testGetSystemsByGroup(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $response = $this->sendGet('/systems', ['group' => 'someGroup']);
        $this->assertEquals(200, $response->status);
        $this->assertEquals(
            [
                (object) $this->getArrayDataForAssert(),
                (object) $this->getArrayDataForAssert(),
            ],
            $response->content
        );
    }

    /**
     *
     */
    public function testGetSystemsByGroupNotFound(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $response = $this->sendGet('/systems', ['group' => 'unknown']);
        $this->assertEquals(500, $response->status);
        $this->assertEquals(SystemException::class, $response->content->type);
        $this->assertEquals(SystemException::SYSTEM_PROPERTY_NOT_FOUND, $response->content->error_code);
    }

    /**
     *
     */
    public function testGetSystemsByUserAndGroup(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $response = $this->sendGet('/systems', ['user' => 'someUser', 'group' => 'someGroup']);
        $this->assertEquals(200, $response->status);
        $this->assertEquals(
            [(object) $this->getArrayDataForAssert()],
            $response->content
        );
    }

    /**
     *
     */
    public function testGetUserSystems(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $system1 = (new SystemInstall())
            ->setUser('someUser')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system1);

        $system2 = (new SystemInstall())
            ->setUser('someUser')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system2);

        $response = $this->sendGet('/user_systems/user/someUser');
        $this->assertEquals(200, $response->status);
        $this->assertEquals(
            [
                (object) array_merge($this->getArrayDataForAssert($system1), ['authorized' => FALSE]),
                (object) array_merge($this->getArrayDataForAssert($system2), ['authorized' => FALSE]),
            ],
            $response->content
        );
    }

    /**
     *
     */
    public function testGetUserSystemsNotFound(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $system = (new SystemInstall())
            ->setUser('someUser')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $response = $this->sendGet('/user_systems/user/unknown');
        $this->assertEquals(200, $response->status);
        $this->assertEquals([], $response->content);
    }

    /**
     *
     */
    public function testInstallSystem(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $response = $this->sendPost('/user_systems/user/someUser/system/null.user.group/install', [
            'token' => 'token',
        ]);
        $this->assertEquals(200, $response->status);
        $this->assertEquals([], $response->content);

        /** @var SystemInstall[] $systems */
        $systems = $this->dm->getRepository(SystemInstall::class)->findBy([
            'system' => 'null.user.group',
            'user'   => 'someUser',
        ]);
        $this->assertEquals(1, count($systems));
    }

    /**
     *
     */
    public function testInstallSystemNotFound(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $response = $this->sendPost('/user_systems/user/someUser/system/unknown/install', [
            'token' => 'token',
        ]);
        $this->assertEquals(500, $response->status);
        $this->assertEquals(SystemException::class, $response->content->type);
        $this->assertEquals(SystemException::SYSTEM_NOT_FOUND, $response->content->error_code);
    }

    /**
     *
     */
    public function testUninstallSystem(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $system = (new SystemInstall())
            ->setUser('someUser')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $response = $this->sendGet('/user_systems/user/someUser/system/null.user.group/uninstall');
        $this->assertEquals(200, $response->status);
        $this->assertEquals([], $response->content);

        /** @var SystemInstall[] $systems */
        $systems = $this->dm->getRepository(SystemInstall::class)->findBy([
            'system' => 'null.user.group',
            'user'   => 'someUser',
        ]);
        $this->assertEquals(0, count($systems));
    }

    /**
     *
     */
    public function testUninstallSystemNotFoundSystem(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $system = (new SystemInstall())
            ->setUser('someUser')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $response = $this->sendGet('/user_systems/user/someUser/system/unknown/uninstall');
        $this->assertEquals(500, $response->status);
        $this->assertEquals(SystemException::class, $response->content->type);
        $this->assertEquals(SystemException::SYSTEM_OR_USER_NOT_FOUND, $response->content->error_code);
    }

    /**
     *
     */
    public function testUninstallSystemNotFoundUser(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $system = (new SystemInstall())
            ->setUser('someUser')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $response = $this->sendGet('/user_systems/user/unknown/system/null.user.group/uninstall');
        $this->assertEquals(500, $response->status);
        $this->assertEquals(SystemException::class, $response->content->type);
        $this->assertEquals(SystemException::SYSTEM_OR_USER_NOT_FOUND, $response->content->error_code);
    }

    /**
     *
     */
    public function testSwitchToken(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $system = (new SystemInstall())
            ->setUser('someUser')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $response = $this->sendPut(
            '/user_systems/user/someUser/system/null.user.group/switch_token',
            ['token' => 'anotherToken']
        );
        $this->assertEquals(200, $response->status);

        $this->dm->clear();

        /** @var SystemInstall[] $systems */
        $systems = $this->dm->getRepository(SystemInstall::class)->findBy([
            'user'   => 'someUser',
            'system' => 'null.user.group',
        ]);
        $this->assertEquals(1, count($systems));
        $this->assertEquals('anotherToken', $systems[0]->getToken());
    }

    /**
     *
     */
    public function testSwitchTokenNotFoundSystem(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $system = (new SystemInstall())
            ->setUser('someUser')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $response = $this->sendPut(
            '/user_systems/user/someUser/system/unknown/switch_token',
            ['token' => 'anotherToken']
        );
        $this->assertEquals(500, $response->status);
        $this->assertEquals(SystemException::class, $response->content->type);
        $this->assertEquals(SystemException::SYSTEM_OR_USER_NOT_FOUND, $response->content->error_code);
    }

    /**
     *
     */
    public function testSwitchTokenNotFoundUser(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $system = (new SystemInstall())
            ->setUser('someUser')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $response = $this->sendPut(
            '/user_systems/user/unknown/system/null.user.group/switch_token',
            ['token' => 'anotherToken']
        );
        $this->assertEquals(500, $response->status);
        $this->assertEquals(SystemException::class, $response->content->type);
        $this->assertEquals(SystemException::SYSTEM_OR_USER_NOT_FOUND, $response->content->error_code);
    }

    /**
     *
     */
    public function testSynchronizeSubscriptions(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $response = $this->sendGet('user_systems/user/someUser/system/null.user.group/sync');
        $this->assertEquals(202, $response->status);
    }

    /**
     *
     */
    public function testSetPassword(): void
    {
        $this->loginUser('user@example.com', 'pass');

        $system = (new SystemInstall())
            ->setUser('someUser')
            ->setSystem('null.user.group')
            ->setToken('token')
            ->setSettings(['password' => 'pass1']);
        $this->persistAndFlush($system);

        $response = $this->sendPut(
            '/user_systems/user/someUser/system/null.user.group/set_password',
            ['password' => 'pass2']
        );
        $this->assertEquals(200, $response->status);

        $this->dm->clear();

        /** @var SystemInstall[] $systems */
        $systems = $this->dm->getRepository(SystemInstall::class)->findBy([
            'user'   => 'someUser',
            'system' => 'null.user.group',
        ]);

        $this->assertEquals(1, count($systems));
        $this->assertEquals('pass2', $systems[0]->getSettings()['password']);
    }

    /**
     * @param SystemInstall|null $systemInstall
     *
     * @return array
     */
    private function getArrayDataForAssert(?SystemInstall $systemInstall = NULL): array
    {
        $arr = [
            'type'        => SystemTypeEnum::CRON,
            'key'         => 'null.user.group',
            'name'        => 'NULL',
            'description' => 'Only for testing purposes',
            'authType'    => 'oauth2',
        ];

        if ($systemInstall) {
            $arr['token']        = $systemInstall->getToken();
            $arr['synchronized'] = $systemInstall->isSynchronized();
        }

        return $arr;
    }

}