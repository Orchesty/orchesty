<?php declare(strict_types=1);

namespace Tests\Controller\HbPFUserBundle\Controller;

use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\PipesFramework\Acl\Document\Rule;
use Hanaboso\PipesFramework\Acl\Exception\AclException;
use Hanaboso\PipesFramework\User\Document\TmpUser;
use Hanaboso\PipesFramework\User\Document\Token;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\Security\SecurityManagerException;
use Hanaboso\PipesFramework\User\Model\Token\TokenManagerException;
use Hanaboso\PipesFramework\User\Model\User\UserManagerException;
use Nette\Utils\Strings;
use Tests\ControllerTestCaseAbstract;

/**
 * Class UserControllerTest
 *
 * @package Tests\Controller\HbPFUserBundle\Controller
 */
class UserControllerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    protected function setUp(): void
    {
        // Intentionally not calling parent setUp
        $this->client = self::createClient([], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->dm->getConnection()->dropDatabase('pipes');
        $this->session->invalidate();
        $this->session->clear();
    }

    /**
     *
     */
    public function testLogin(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $response = $this->sendPost('api/gateway/user/login', [
            'email'    => $user->getEmail(),
            'password' => 'passw0rd',
        ]);

        $this->assertEquals(200, $response->status);
        $this->assertEquals($user->getEmail(), $response->content->email);
        $this->assertEquals($user->getPassword(), $response->content->password);
    }

    /**
     *
     */
    public function testLoginNotFoundEmail(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $response = $this->sendPost('/api/gateway/user/login', [
            'email'    => '',
            'password' => '',
        ]);

        $this->assertEquals(500, $response->status);
        $this->assertEquals(SecurityManagerException::class, $response->content->type);
        $this->assertEquals(SecurityManagerException::USER_OR_PASSWORD_NOT_VALID, $response->content->error_code);
    }

    /**
     *
     */
    public function testLoginNotFoundPassword(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $response = $this->sendPost('/api/gateway/user/login', [
            'email'    => $user->getEmail(),
            'password' => '',
        ]);

        $this->assertEquals(500, $response->status);
        $this->assertEquals(SecurityManagerException::class, $response->content->type);
        $this->assertEquals(SecurityManagerException::USER_OR_PASSWORD_NOT_VALID, $response->content->error_code);
    }

    /**
     *
     */
    public function testLogout(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $response = $this->sendPost('/api/gateway/user/logout', []);

        $this->assertEquals(200, $response->status);
    }

    /**
     *
     */
    public function testLogoutNotLogged(): void
    {
        $response = $this->sendPost('/api/gateway/user/logout', []);

        $this->assertEquals(500, $response->status);
        $this->assertEquals(SecurityManagerException::class, $response->content->type);
        $this->assertEquals(SecurityManagerException::USER_NOT_LOGGED, $response->content->error_code);
    }

    /**
     *
     */
    public function testRegister(): void
    {
        $response = $this->sendPost('/api/gateway/user/register', [
            'email' => 'email@example.com',
        ]);

        $this->assertEquals(200, $response->status);
    }

    /**
     *
     */
    public function testRegisterNotUniqueEmail(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $response = $this->sendPost('/api/gateway/user/register', [
            'email' => 'email@example.com',
        ]);

        $this->assertEquals(500, $response->status);
        $this->assertEquals(UserManagerException::class, $response->content->type);
        $this->assertEquals(UserManagerException::USER_EMAIL_ALREADY_EXISTS, $response->content->error_code);
    }

    /**
     *
     */
    public function testActivate(): void
    {
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $token = (new Token())->setTmpUser($user);
        $this->persistAndFlush($token);

        $response = $this->sendPost(sprintf('/api/gateway/user/%s/activate', $token->getId()), []);

        $this->assertEquals(200, $response->status);
    }

    /**
     *
     */
    public function testActivateNotValid(): void
    {
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $token = (new Token())->setTmpUser($user);
        $this->persistAndFlush($token);

        $response = $this->sendPost(sprintf('/api/gateway/user/%s/activate', Strings::substring($token->getId(), 1)),
            []);

        $this->assertEquals(500, $response->status);
        $this->assertEquals(TokenManagerException::class, $response->content->type);
        $this->assertEquals(TokenManagerException::TOKEN_NOT_VALID, $response->content->error_code);
    }

    /**
     *
     */
    public function testSetPassword(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $user = (new User())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $token = (new Token())->setUser($user);
        $this->persistAndFlush($token);

        $response = $this->sendPost(sprintf('/api/gateway/user/%s/set_password', $token->getId()),
            ['password' => 'newPassword']);

        $this->assertEquals(200, $response->status);
    }

    /**
     *
     */
    public function testSetPasswordNotValid(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $user = (new User())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $token = (new Token())->setUser($user);
        $this->persistAndFlush($token);

        $response = $this->sendPost(sprintf('/api/gateway/user/%s/set_password',
            Strings::substring($token->getId(), 1)), ['password' => 'newPassword']);

        $this->assertEquals(500, $response->status);
        $this->assertEquals(TokenManagerException::class, $response->content->type);
        $this->assertEquals(TokenManagerException::TOKEN_NOT_VALID, $response->content->error_code);
    }

    /**
     *
     */
    public function testChangePassword(): void
    {
        $user     = $this->loginUser('email@example.com', 'passw0rd');
        $response = $this->sendPost('/api/gateway/user/change_password', ['password' => 'anotherPassw0rd']);

        $this->dm->clear();
        $existingUser = $this->dm->getRepository(User::class)->find($user->getId());

        $this->assertEquals(200, $response->status);
        $this->assertNotSame($user->getPassword(), $existingUser->getPassword());
    }

    /**
     *
     */
    public function testChangePasswordNotLogged(): void
    {
        $response = $this->sendPost('/api/gateway/user/change_password', ['password' => 'anotherPassw0rd']);

        $this->assertEquals(403, $response->status);
    }

    /**
     *
     */
    public function testResetPassword(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $user = (new User())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $token = (new Token())->setUser($user);
        $this->persistAndFlush($token);

        $response = $this->sendPost('/api/gateway/user/reset_password', [
            'email' => $user->getEmail(),
        ]);

        $this->assertEquals(200, $response->status);
    }

    /**
     *
     */
    public function testResetPasswordNotFoundEmail(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $user = (new User())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $token = (new Token())->setUser($user);
        $this->persistAndFlush($token);

        $response = $this->sendPost('/api/gateway/user/reset_password', [
            'email' => '',
        ]);

        $this->assertEquals(500, $response->status);
        $this->assertEquals(UserManagerException::class, $response->content->type);
        $this->assertEquals(UserManagerException::USER_EMAIL_NOT_EXISTS, $response->content->error_code);
    }

    /**
     *
     */
    public function testDelete(): void
    {
        $loggedUser = $this->loginUser('email@example.com', 'passw0rd');

        $rule = (new Rule())
            ->setPropertyMask(2)
            ->setActionMask(7)
            ->setResource('user');
        $this->persistAndFlush($rule);

        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword('passw0rd');
        $this->persistAndFlush($user);

        $group = (new Group($loggedUser))
            ->setName('Group')
            ->addUser($loggedUser)
            ->addRule($rule);
        $this->persistAndFlush($group);

        $rule->setGroup($group);
        $this->dm->flush();

        $response = $this->sendDelete(sprintf('/api/gateway/user/%s/delete', $user->getId()));

        $this->assertEquals(200, $response->status);
        $this->assertEquals($user->getEmail(), $response->content->email);
        $this->assertEquals($user->getPassword(), $response->content->password);
    }

    /**
     *
     */
    public function testDeleteMissing(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $response = $this->sendDelete('/api/gateway/user/0/delete');

        $this->assertEquals(500, $response->status);
        $this->assertEquals(UserManagerException::class, $response->content->type);
        $this->assertEquals(UserManagerException::USER_NOT_EXISTS, $response->content->error_code);
    }

    /**
     *
     */
    public function testDeleteYourself(): void
    {
        $loggedUser = $this->loginUser('email@example.com', 'passw0rd');

        $rule = (new Rule())
            ->setPropertyMask(2)
            ->setActionMask(7)
            ->setResource('user');
        $this->persistAndFlush($rule);

        $group = (new Group($loggedUser))
            ->setName('Group')
            ->addUser($loggedUser)
            ->addRule($rule);
        $this->persistAndFlush($group);

        $rule->setGroup($group);
        $this->dm->flush();

        $response = $this->sendDelete(sprintf('/api/gateway/user/%s/delete', $loggedUser->getId()));

        $this->assertEquals(500, $response->status);
        $this->assertEquals(UserManagerException::class, $response->content->type);
        $this->assertEquals(UserManagerException::USER_DELETE_NOT_ALLOWED, $response->content->error_code);
    }

    /**
     *
     */
    public function testDeleteNoAccess(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword('passw0rd');
        $this->persistAndFlush($user);

        $response = $this->sendDelete(sprintf('/api/gateway/user/%s/delete', $user->getId()));

        $this->assertEquals(500, $response->status);
        $this->assertEquals(AclException::class, $response->content->type);
        $this->assertEquals(AclException::PERMISSION, $response->content->error_code);
    }

}