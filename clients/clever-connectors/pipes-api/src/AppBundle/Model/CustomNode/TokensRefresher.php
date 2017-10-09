<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;

/**
 * Class TokensRefresher
 *
 * @package CleverConnectors\AppBundle\Model\CustomNode
 */
class TokensRefresher implements CustomNodeInterface
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
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        /** @var SystemInstall $systemInstall */
        $systemInstall = Json::decode($dto->getData());

        /** @var OAuth2Interface $system */
        $system = $this->loader->getSystem($systemInstall->getSystem());
        $system->refreshToken($systemInstall);
        $this->dm->flush();

        return $dto;
    }

}