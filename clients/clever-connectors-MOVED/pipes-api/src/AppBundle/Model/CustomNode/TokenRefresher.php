<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class TokensRefresher
 *
 * @package CleverConnectors\AppBundle\Model\CustomNode
 */
class TokenRefresher implements CustomNodeInterface
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var SystemLoader
     */
    private $loader;

    /**
     * TokensRefresher constructor.
     *
     * @param DocumentManager $dm
     * @param SystemLoader    $loader
     */
    public function __construct(DocumentManager $dm, SystemLoader $loader)
    {
        $this->dm     = $dm;
        $this->loader = $loader;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws SystemException
     * @throws LockException
     * @throws MappingException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $systemInstall = json_decode($dto->getData(), TRUE);
        /** @var SystemInstall $systemInstall */
        $systemInstall = $this->dm->getRepository(SystemInstall::class)->find($systemInstall['_id']['$id']);

        if (!$systemInstall->getExpires()) {
            return $dto;
        }

        /** @var OAuth2Interface $system */
        $system = $this->loader->getSystem($systemInstall->getSystem());
        $system->refreshToken($systemInstall);

        $this->dm->flush();

        return $dto;
    }

}