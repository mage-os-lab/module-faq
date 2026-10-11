<?php

declare(strict_types=1);

namespace MageOS\Faq\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MageOS\Seo\Api\FaqSourcePoolInterface;

/**
 * The FAQ groups to pick from: the FAQ List widget's and the Page Builder FAQ element's group.
 *
 * Every group any FAQ source has, each once and sorted, as the elements render through the source
 * pool. A group whose entries are all inactive is still a group: entries are activated without
 * moving the element that shows them.
 */
class Groups implements OptionSourceInterface
{
    /**
     * @param FaqSourcePoolInterface $sourcePool
     */
    public function __construct(
        private readonly FaqSourcePoolInterface $sourcePool
    ) {
    }

    /**
     * @inheritdoc
     *
     * An empty option comes first. A select shows a stored value it has no option for as its first
     * option, and a save then writes that option. An element saved with an identifier that is no
     * group renders nothing, so it shows as nothing picked, and the required field asks for a group,
     * rather than showing, and on save switching to, the first group.
     *
     * @return array<int, array{value: string, label: string|\Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('-- Please Select --')]];
        foreach ($this->sourcePool->getIdentifiers() as $identifier) {
            $options[] = ['value' => $identifier, 'label' => $identifier];
        }

        return $options;
    }
}
