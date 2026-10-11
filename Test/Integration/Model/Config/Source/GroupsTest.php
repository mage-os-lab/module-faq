<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration\Model\Config\Source;

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Model\Config\Source\Groups;
use MageOS\Faq\Model\Faq;
use PHPUnit\Framework\TestCase;

/**
 * Review low item: a FAQ group is picked from the groups that exist, not typed.
 *
 * The widget's and the Page Builder element's group identifier were free text, so a typo showed an
 * empty FAQ block with nothing to say why. Their options are each group once, in order. A group
 * whose entries are all inactive is still listed: it is a group, and entries are activated without
 * moving the widget that shows them.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class GroupsTest extends TestCase
{
    /**
     * @return void
     */
    public function testEachGroupOnceInOrder(): void
    {
        $this->faq('zz-faq-groups-b', true);
        $this->faq('zz-faq-groups-a', true);
        $this->faq('zz-faq-groups-a', true);
        $this->faq('zz-faq-groups-c', false);

        $values = array_values(array_filter(
            array_column(Bootstrap::getObjectManager()->create(Groups::class)->toOptionArray(), 'value'),
            static fn ($value): bool => str_starts_with((string) $value, 'zz-faq-groups-')
        ));

        $this->assertSame(['zz-faq-groups-a', 'zz-faq-groups-b', 'zz-faq-groups-c'], $values);
    }

    /**
     * A select shows a stored value it has no option for as its first option, and a save writes it.
     * An element saved with an identifier that is no group renders nothing, so the first option is
     * nothing picked, not some group the save would switch it to.
     *
     * @return void
     */
    public function testTheFirstOptionIsNoGroup(): void
    {
        $this->faq('zz-faq-groups-a', true);

        $options = Bootstrap::getObjectManager()->create(Groups::class)->toOptionArray();

        $this->assertSame('', $options[0]['value'] ?? null);
    }

    /**
     * Save an entry for all store views.
     *
     * @param string $identifier
     * @param bool $isActive
     * @return void
     */
    private function faq(string $identifier, bool $isActive): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Faq $faq */
        $faq = $objectManager->create(Faq::class);
        $faq->setIdentifier($identifier);
        $faq->setStoreId(0);
        $faq->setQuestion('Q ' . uniqid());
        $faq->setAnswer('A');
        $faq->setIsActive($isActive);
        $objectManager->get(FaqRepositoryInterface::class)->save($faq);
    }
}
