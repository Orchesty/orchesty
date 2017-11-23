<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\DataLayout;

use CleverConnectors\AppBundle\Document\DataLayout;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\DataLayoutActionEnum;
use CleverConnectors\AppBundle\Enum\TypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\DataLayout\Exceptions\LayoutException;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class LayoutManager
 *
 * @package CleverConnectors\AppBundle\Model\DataLayout
 */
class LayoutManager
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var SystemLoader
     */
    private $systemLoader;

    /**
     * LayoutManager constructor.
     *
     * @param DocumentManager $dm
     * @param SystemLoader    $systemLoader
     */
    public function __construct(DocumentManager $dm, SystemLoader $systemLoader)
    {
        $this->dm           = $dm;
        $this->systemLoader = $systemLoader;
    }

    /**
     * @param string $id
     *
     * @return DataLayout
     * @throws CleverConnectorsException
     */
    public function get(string $id): DataLayout
    {
        $layout = $this->dm->getRepository(DataLayout::class)->find($id);

        if (!$layout) {
            throw new CleverConnectorsException(
                'DataLayout not found',
                CleverConnectorsException::DATALAYOUT_NOT_FOUND
            );
        }

        return $layout;
    }

    /**
     * @param SystemInstall $systemInstall
     */
    public function removeBySystemInstall(SystemInstall $systemInstall): void
    {
        $dataLayouts = $this->dm->getRepository(DataLayout::class)->findBy([
            'systemInstall' => $systemInstall->getId(),
        ]);

        if ($dataLayouts) {
            foreach ($dataLayouts as $dataLayout) {
                $this->dm->remove($dataLayout);
            }

            $this->dm->flush();
        }
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return DataLayout
     * @throws LayoutException
     */
    public function createDataLayout(SystemInstall $systemInstall, array $data): DataLayout
    {
        $this->checkDynamicMapping($systemInstall);

        $dataLayout = $this->dm->getRepository(DataLayout::class)->findOneBy([
            'systemInstall' => $systemInstall->getId(),
            'action'        => $data['action'],
        ]);

        if ($dataLayout) {
            throw new LayoutException(
                sprintf(
                    'System Install \'%s\' already has DataLayout for action \'%s\'',
                    $systemInstall->getId(),
                    $data['action']
                ),
                LayoutException::DATA_LAYOUT_ALREADY_EXISTS
            );
        }

        $dataLayout = (new DataLayout())
            ->setSystemInstall($systemInstall)
            ->setAction(new DataLayoutActionEnum($data['action']));

        foreach ($data['fields'] as $field) {
            if (isset($field['key']) && isset($field['type'])) {
                $dataLayout->addField(new LayoutField($field['key'], new TypeEnum($field['type'])));
            }
        }

        $this->dm->persist($dataLayout);
        $this->dm->flush();

        return $dataLayout;
    }

    /**
     * @param DataLayout $dataLayout
     * @param array      $data
     *
     * @return DataLayout
     */
    public function updateDataLayout(DataLayout $dataLayout, array $data): DataLayout
    {
        if (isset($data['fields'])) {
            $dataLayout->setFields([]);
            foreach ($data['fields'] as $field) {
                if (isset($field['key']) && isset($field['type'])) {
                    $dataLayout->addField(new LayoutField($field['key'], new TypeEnum($field['type'])));
                }
            }
        }

        $this->dm->flush();

        return $dataLayout;
    }

    /**
     * @param DataLayout $dataLayout
     *
     * @return array
     */
    public function deleteDataLayout(DataLayout $dataLayout): array
    {
        $this->dm->remove($dataLayout);
        $this->dm->flush();

        return [];
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

}