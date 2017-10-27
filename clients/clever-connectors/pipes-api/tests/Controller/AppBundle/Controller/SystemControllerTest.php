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
final class SystemControllerTest extends ControllerTestCaseAbstract
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
        $this->assertEquals(404, $response->status);
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
        $res      = json_decode($response->content);

        $this->assertEquals(500, $response->status);
        $this->assertEquals(SystemException::class, $res->type);
        $this->assertEquals(2001, $res->error_code);
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
        $res      = json_decode($response->content);
        $this->assertEquals(500, $response->status);
        $this->assertEquals(SystemException::class, $res->type);
        $this->assertEquals(2001, $res->error_code);
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
    public function testGetUserSystem(): void
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

        $response = $this->sendGet('/user_systems/user/someUser/system/null.user.group');
        $this->assertEquals(200, $response->status);
        $this->assertEquals(
            (object) array_merge($this->getArrayDataForAssert($system1), [
                'authorized'     => FALSE,
                'setting_fields' => [
                    (object) [
                        'type'        => 'url',
                        'key'         => 'field1',
                        'label'       => '',
                        'value'       => NULL,
                        'required'    => TRUE,
                        'read_only'   => FALSE,
                        'disabled'    => FALSE,
                        'description' => '',
                    ],
                    (object) [
                        'type'        => 'text',
                        'key'         => 'field2',
                        'label'       => '',
                        'value'       => NULL,
                        'required'    => TRUE,
                        'read_only'   => FALSE,
                        'disabled'    => FALSE,
                        'description' => '',
                    ],
                    (object) [
                        'type'        => 'password',
                        'key'         => 'field3',
                        'label'       => '',
                        'value'       => NULL,
                        'required'    => TRUE,
                        'read_only'   => FALSE,
                        'disabled'    => FALSE,
                        'description' => '',
                    ],
                ],
            ]),
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

        /** @var SystemInstall[] $system */
        $system = $this->dm->getRepository(SystemInstall::class)->findOneBy([
            'system' => 'null.user.group',
            'user'   => 'someUser',
        ]);
        $this->assertEquals(1, count($system));
        $this->assertEquals(200, $response->status);
        $this->assertEquals(
            (object) array_merge($this->getArrayDataForAssert($system), [
                'authorized'     => FALSE,
                'setting_fields' => [
                    (object) [
                        'type'        => 'url',
                        'key'         => 'field1',
                        'label'       => '',
                        'value'       => NULL,
                        'required'    => TRUE,
                        'read_only'   => FALSE,
                        'disabled'    => FALSE,
                        'description' => '',
                    ],
                    (object) [
                        'type'        => 'text',
                        'key'         => 'field2',
                        'label'       => '',
                        'value'       => NULL,
                        'required'    => TRUE,
                        'read_only'   => FALSE,
                        'disabled'    => FALSE,
                        'description' => '',
                    ],
                    (object) [
                        'type'        => 'password',
                        'key'         => 'field3',
                        'label'       => '',
                        'value'       => FALSE,
                        'required'    => TRUE,
                        'read_only'   => FALSE,
                        'disabled'    => FALSE,
                        'description' => '',
                    ],
                ],
            ]),
            $response->content
        );
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
        $res      = json_decode($response->content);
        $this->assertEquals(404, $response->status);
        $this->assertEquals(SystemException::class, $res->type);
        $this->assertEquals(2001, $res->error_code);
    }

    /**
     *
     */
    public function testSaveSystemSettings(): void
    {
        $this->loginUser('user@example.com', 'pass');
        $system = (new SystemInstall())
            ->setUser('someUser')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $response = $this->sendPost('/user_systems/user/someUser/system/null.user.group/settings', [
            'settingOne'            => 'settingOne',
            'settingTwo'            => 'settingTwo',
            'password'              => NULL,
            'frontend_redirect_url' => NULL,
        ]);

        $this->assertEquals(200, $response->status);
        $this->assertEquals([], $response->content);

        $this->dm->clear();

        /** @var SystemInstall[] $systems */
        $systems = $this->dm->getRepository(SystemInstall::class)->findBy([
            'system' => 'null.user.group',
            'user'   => 'someUser',
        ]);

        $this->assertEquals(1, count($systems));
        $this->assertEquals([
            'settingOne'            => 'settingOne',
            'settingTwo'            => 'settingTwo',
            'password'              => NULL,
            'frontend_redirect_url' => NULL,
        ], $systems[0]->getSettings());

        $systems[0]->setSettings(['setting' => 'setting', 'password' => 'passw0rd']);
        $this->dm->flush();

        $response = $this->sendPost('/user_systems/user/someUser/system/null.user.group/settings', [
            'settingOne' => 'settingOne',
            'settingTwo' => 'settingTwo',
        ]);

        $this->assertEquals(200, $response->status);
        $this->assertEquals([], $response->content);

        $this->dm->clear();

        /** @var SystemInstall[] $systems */
        $systems = $this->dm->getRepository(SystemInstall::class)->findBy([
            'system' => 'null.user.group',
            'user'   => 'someUser',
        ]);

        $this->assertEquals(1, count($systems));
        $this->assertEquals([
            'settingOne' => 'settingOne',
            'settingTwo' => 'settingTwo',
            'password'   => 'passw0rd',
            'setting'    => 'setting',
        ], $systems[0]->getSettings());
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
        $res      = json_decode($response->content);
        $this->assertEquals(404, $response->status);
        $this->assertEquals(SystemException::class, $res->type);
        $this->assertEquals(2001, $res->error_code);
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
        $res      = json_decode($response->content);
        $this->assertEquals(404, $response->status);
        $this->assertEquals(SystemException::class, $res->type);
        $this->assertEquals(2001, $res->error_code);
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
        $res      = json_decode($response->content);
        $this->assertEquals(404, $response->status);
        $this->assertEquals(SystemException::class, $res->type);
        $this->assertEquals(2001, $res->error_code);
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
        $res      = json_decode($response->content);
        $this->assertEquals(404, $response->status);
        $this->assertEquals(SystemException::class, $res->type);
        $this->assertEquals(2001, $res->error_code);
    }

    /**
     *
     */
    public function testSynchronizeSubscriptions(): void
    {
        $system = (new SystemInstall())
            ->setUser('someUser')
            ->setSystem('null.user.group')
            ->setToken('token')
            ->setSettings(['password' => 'pass1']);
        $this->persistAndFlush($system);

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
            'auth_type'    => 'oauth2',
        ];

        if ($systemInstall) {
            $arr['token']        = $systemInstall->getToken();
            $arr['synchronized'] = $systemInstall->isSynchronized();
        }

        return $arr;
    }

}