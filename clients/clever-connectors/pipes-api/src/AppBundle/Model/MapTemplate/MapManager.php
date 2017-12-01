<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\MapTemplate;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Model\Systems\Traits\MapTrait;
use CleverConnectors\AppBundle\Repository\MapTemplateRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class MapManager
 *
 * @package CleverConnectors\AppBundle\Model\MapTemplate
 */
class MapManager
{

    use MapTrait;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var MapTemplateRepository|ObjectRepository
     */
    private $mapTemplateRepository;

    /**
     * @var SystemLoader
     */
    private $systemLoader;

    /**
     * MapManager constructor.
     *
     * @param DocumentManager $documentManager
     * @param SystemLoader    $systemLoader
     */
    public function __construct(DocumentManager $documentManager, SystemLoader $systemLoader)
    {
        $this->dm                    = $documentManager;
        $this->mapTemplateRepository = $this->dm->getRepository(MapTemplate::class);
        $this->systemLoader          = $systemLoader;
    }

    /**
     * @param string $id
     *
     * @return MapTemplate
     * @throws CleverConnectorsException
     */
    public function get(string $id): MapTemplate
    {
        /** @var MapTemplate $mapTemplate */
        $mapTemplate = $this->mapTemplateRepository->find($id);

        if (!$mapTemplate) {
            throw new CleverConnectorsException(
                'Map template not found',
                CleverConnectorsException::MAP_TEMPLATE_NOT_FOUND
            );
        }

        return $mapTemplate;
    }

    /**
     * @param SystemInstall $systemInstall
     */
    public function removeBySystemInstall(SystemInstall $systemInstall): void
    {
        $mapTemplates = $this->mapTemplateRepository->findBy([
            'systemInstall' => $systemInstall->getId(),
        ]);

        if ($mapTemplates) {
            foreach ($mapTemplates as $mapTemplate) {
                $this->dm->remove($mapTemplate);
            }

            $this->dm->flush();
        }
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return MapTemplate
     * @throws CleverConnectorsException
     */
    public function create(SystemInstall $systemInstall, array $data): MapTemplate
    {
        $system = $this->systemLoader->getSystem($systemInstall->getSystem());
        $this->checkDynamicMapping($system);
        $actionDto   = $this->checkAction($system, $data);
        $mapTemplate = $this->mapTemplateRepository->findUnique($systemInstall, $actionDto);

        if ($mapTemplate) {
            return $this->update($mapTemplate, $data);
        }

        $mapTemplate = new MapTemplate();
        $mapTemplate = $this->fillMapTemplate($mapTemplate, $data);
        $mapTemplate
            ->setAction($actionDto)
            ->setDirection($actionDto)
            ->setSystemInstall($systemInstall);

        $this->dm->persist($mapTemplate);
        $this->dm->flush();

        return $mapTemplate;
    }

    /**
     * @param MapTemplate $mapTemplate
     * @param array       $data
     *
     * @return MapTemplate
     */
    public function update(MapTemplate $mapTemplate, array $data): MapTemplate
    {
        $mapTemplate = $this->fillMapTemplate($mapTemplate, $data);

        $this->dm->flush();

        return $mapTemplate;
    }

    /**
     * @param MapTemplate $mapTemplate
     */
    public function delete(MapTemplate $mapTemplate): void
    {
        $this->dm->remove($mapTemplate);
        $this->dm->flush();
    }

    /**
     * @param MapTemplate $mapTemplate
     * @param array       $data
     *
     * @return MapTemplate
     */
    private function fillMapTemplate(MapTemplate $mapTemplate, array $data): MapTemplate
    {
        $mapTemplate->setFields([]);
        if (array_key_exists('fields', $data) && !empty($data['fields'])) {
            foreach ($data['fields'] as $field) {
                $this->addMapField($mapTemplate, $field);
            }
        }

        return $mapTemplate;
    }

    /**
     * @param MapTemplate $mapTemplate
     * @param array       $data
     *
     * @return MapTemplate
     */
    private function addMapField(MapTemplate $mapTemplate, array $data): MapTemplate
    {
        $mapField = MapField::from($data);

        if ($mapField) {
            $mapTemplate->addField($mapField);
        }

        return $mapTemplate;
    }

}