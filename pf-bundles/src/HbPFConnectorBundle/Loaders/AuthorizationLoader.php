<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.3.2017
 * Time: 16:25
 */

namespace Hanaboso\PipesFramework\HbPFConnectorBundle\Loaders;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Authorizations\Document\Authorization;
use Hanaboso\PipesFramework\Authorizations\Repository\AuthorizationRepository;
use Hanaboso\PipesFramework\Commons\Authorization\Connectors\AuthorizationInterface;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Exception\AuthorizationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class AuthorizationRepository
 *
 * @package Hanaboso\PipesFramework\Commons\Authorization\Connectors
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
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->dm        = $container->get('doctrine_mongodb.odm.default_document_manager');
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
        $list = Yaml::parse(file_get_contents(__DIR__ . '/../Resources/config/authorizations.yml'));
        $res  = [];

        foreach ($list['services'] as $key => $item) {
            $shortened = str_replace(self::AUTHORIZATION_PREFIX . '.', '', $key);
            if (in_array($shortened, $exclude)) {
                unset($exclude[$shortened]);
                continue;
            }
            $res[] = $shortened;
        }

        return $res;
    }

    /**
     * @return string[]
     */
    public function getAllAuthorizationsInfo(): array
    {
        $authorizations = $this->getInstalled();
        $res            = [];

        foreach ($authorizations as $authorization) {
            $authorizationService = $this->getAuthorization($authorization);

            $res[$authorization] = $authorizationService->getInfo();
        }

        return $res;
    }

    /**
     *
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
     */
    private function getInstalled(): array
    {
        // Check for PHPStan
        $repo = $this->dm->getRepository(Authorization::class);
        if ($repo instanceof AuthorizationRepository) {
            return $repo->getInstalledKeys();
        }

        return [];
    }

}