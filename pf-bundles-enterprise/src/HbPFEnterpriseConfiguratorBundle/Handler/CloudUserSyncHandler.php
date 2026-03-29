<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\AclBundle\Manager\GroupManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\ControllerUtils;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Throwable;

/**
 * Class CloudUserSyncHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class CloudUserSyncHandler
{

    private const array ROLE_MAP = [
        'OWNER'     => 'admin',
        'ADMIN'     => 'admin',
        'DEVELOPER' => 'user',
        'BILLING'   => 'user',
    ];

    private const string DEFAULT_GROUP = 'user';

    /**
     * CloudUserSyncHandler constructor.
     *
     * @param CurlManager                  $curlManager
     * @param DocumentManager              $dm
     * @param PasswordHasherFactoryInterface $passwordHasherFactory
     * @param GroupManager                 $groupManager
     * @param string                       $cloudUrl
     */
    public function __construct(
        private readonly CurlManager $curlManager,
        private readonly DocumentManager $dm,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly GroupManager $groupManager,
        private readonly string $cloudUrl,
    )
    {
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function syncUsers(array $data): array
    {
        ControllerUtils::checkParameters(['token'], $data);

        $users   = $this->fetchUsersFromCloud($data['token']);
        $created = 0;
        $skipped = 0;

        foreach ($users as $cloudUser) {
            $email = $cloudUser['email'] ?? NULL;
            if (!$email) {
                $skipped++;

                continue;
            }

            $existing = $this->dm->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existing) {
                $skipped++;

                continue;
            }

            $user = new User();
            $user->setEmail($email);

            $hasher = $this->passwordHasherFactory->getPasswordHasher(User::class);
            $user->setPassword($hasher->hash(bin2hex(random_bytes(32))));

            $this->dm->persist($user);
            $this->dm->flush();

            $groupName = self::ROLE_MAP[$cloudUser['role'] ?? ''] ?? self::DEFAULT_GROUP;

            try {
                $this->groupManager->addUserIntoGroup($user, groupName: $groupName);
            } catch (Throwable) {
                // Group might not exist yet in fresh installation
            }

            $created++;
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'total'   => count($users),
        ];
    }

    /**
     * @param string $token
     *
     * @return mixed[]
     */
    private function fetchUsersFromCloud(string $token): array
    {
        $url = sprintf('%s/api/public/instance-users?token=%s', rtrim($this->cloudUrl, '/'), $token);

        $dto = new RequestDto(
            new Uri($url),
            CurlManager::METHOD_GET,
            new ProcessDto(),
        );

        $response = $this->curlManager->send($dto);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(
                sprintf('Cloud API returned status %d', $response->getStatusCode()),
            );
        }

        $body = $response->getJsonBody();

        return $body['users'] ?? [];
    }

}
