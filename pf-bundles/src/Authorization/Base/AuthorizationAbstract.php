<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Authorization\Base;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Authorization\Document\Authorization;

/**
 * Class AuthorizationAbstract
 *
 * @package Hanaboso\PipesFramework\Authorization\Base
 */
abstract class AuthorizationAbstract implements AuthorizationInterface
{

    protected const ID          = 'id';
    protected const NAME        = 'name';
    protected const DESCRIPTION = 'description';

    /**
     * @var string[]
     */
    private $config = [];

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var Authorization|null
     */
    protected $authorization;

    /**
     * @var bool
     */
    protected $authorized = TRUE;

    /**
     * AuthorizationAbstract constructor.
     *
     * @param string          $id
     * @param string          $name
     * @param string          $description
     * @param DocumentManager $dm
     */
    public function __construct(string $id, string $name, string $description, DocumentManager $dm)
    {
        $this->dm     = $dm;
        $this->config = [
            self::ID          => $id,
            self::NAME        => $name,
            self::DESCRIPTION => $description,
        ];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->config[self::ID];
    }

    /**
     * @param string $hostname
     *
     * @return array
     */
    public function getInfo(string $hostname): array
    {
        return [
            'name'          => $this->config[self::NAME],
            'description'   => $this->config[self::DESCRIPTION],
            'type'          => $this->getAuthorizationType(),
            'is_authorized' => $this->isAuthorized(),
        ];
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    /**
     *
     */
    protected function loadAuthorization(): void
    {
        /** @var Authorization $auth */
        $auth                = $this->dm->getRepository(Authorization::class)->findOneBy([
            'authorizationKey' => $this->getId(),
        ]);
        $this->authorization = $auth;
    }

}