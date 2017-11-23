<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\MapTemplate;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\DataLayoutActionEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
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
        $this->checkDynamicMapping($systemInstall);

        $mapTemplate = $this->mapTemplateRepository->findUnique(
            $systemInstall,
            new DataLayoutActionEnum($data['action']),
            $data['direction']
        );

        if ($mapTemplate) {
            return $this->update($mapTemplate, $data);
        }

        $mapTemplate = new MapTemplate();
        $mapTemplate = $this->fillMapTemplate($mapTemplate, $data);
        $mapTemplate
            ->setAction(new DataLayoutActionEnum($data['action']))
            ->setDirection($data['direction'])
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
     * @param SystemInstall $systemInstall
     *
     * @throws CleverConnectorsException
     */
    private function checkDynamicMapping(SystemInstall $systemInstall): void
    {
        $system = $this->systemLoader->getSystem($systemInstall->getSystem());
        if (!$system->isDynamicMapper()) {
            throw new CleverConnectorsException(
                sprintf('System "%s" does not support dynamic mapping', $systemInstall->getSystem()),
                CleverConnectorsException::DYNAMIC_MAPPING_NOT_ALLOWED
            );
        }
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