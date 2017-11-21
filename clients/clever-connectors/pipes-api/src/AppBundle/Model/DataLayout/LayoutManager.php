<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\DataLayout;

use CleverConnectors\AppBundle\Document\DataLayout;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\DataLayoutActionEnum;
use CleverConnectors\AppBundle\Enum\TypeEnum;
use CleverConnectors\AppBundle\Model\DataLayout\Exceptions\LayoutException;
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
     * LayoutManager constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
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

}