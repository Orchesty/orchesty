<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.3.2017
 * Time: 16:25
 */

namespace Hanaboso\PipesFramework\HbPFAuthorizationBundle\Loader;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Authorization\Base\AuthorizationInterface;
use Hanaboso\PipesFramework\Authorization\Document\Authorization;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Repository\AuthorizationRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class AuthorizationRepository
 *
 * @package Hanaboso\PipesFramework\HbPFAuthorizationBundle\Loader
 */
class AuthorizationLoader
{

    private const AUTHORIZATION_PREFIX = 'hbpf.authorization';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * AuthorizationRepository constructor.
     *
     * @param ContainerInterface $container
     * @param DocumentManager    $dm
     */
    public function __construct(ContainerInterface $container, DocumentManager $dm)
    {
        $this->container = $container;
        $this->dm        = $dm;
    }

    /**
     * @param string $authorizationStr
     *
     * @return AuthorizationInterface
     * @throws AuthorizationException
     */
    public function getAuthorization(string $authorizationStr): AuthorizationInterface
    {
        $name = sprintf('%s.%s', self::AUTHORIZATION_PREFIX, $authorizationStr);

        if ($this->container->has($name)) {
            /** @var AuthorizationInterface $authorization */
            $authorization = $this->container->get($name);
        } else {
            throw new AuthorizationException(
                sprintf('Authorization for [%s] was not found.', $authorizationStr),
                AuthorizationException::AUTHORIZATION_SERVICE_NOT_FOUND
            );
        }

        return $authorization;
    }

    /**
     * @param array $exclude
     *
     * @return string[]
     */
    public function getAllAuthorizations(array $exclude = []): array
    {
        $exclude = array_merge($exclude, ['_defaults']);
        $list    = Yaml::parse((string) file_get_contents(__DIR__ . '/../Resources/config/authorizations.yml'));
        $res     = [];

        foreach (array_keys($list['services']) as $key) {
            $shortened = str_replace(self::AUTHORIZATION_PREFIX . '.', '', (string) $key);
            if (in_array($shortened, $exclude)) {
                unset($exclude[$shortened]);
                continue;
            }
            $res[] = $shortened;
        }

        return $res;
    }

    /**
     * @param string $hostname
     *
     * @return array
     * @throws AuthorizationException
     * @throws MongoDBException
     */
    public function getAllAuthorizationsInfo(string $hostname): array
    {
        $authorizations = $this->getInstalled();
        $res            = [];

        foreach ($authorizations as $authorization) {
            $authorizationService = $this->getAuthorization($authorization);

            $res[] = array_merge($authorizationService->getInfo($hostname), ['key' => $authorization]);
        }

        return $res;
    }

    /**
     * @throws MongoDBException
     */
    public function installAllAuthorizations(): void
    {
        $installed = $this->getInstalled();
        $keys      = $this->getAllAuthorizations($installed);

        foreach ($keys as $key) {
            $auth = new Authorization($key);
            $this->dm->persist($auth);
        }

        $this->dm->flush();
    }

    /**
     * @return string[]
     * @throws MongoDBException
     */
    private function getInstalled(): array
    {
        /** @var AuthorizationRepository $repo $repo */
        $repo = $this->dm->getRepository(Authorization::class);

        return $repo->getInstalledKeys();
    }

}
