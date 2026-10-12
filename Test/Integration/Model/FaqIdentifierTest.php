<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration\Model;

use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Block\FaqJsonLd;
use MageOS\Faq\Block\Widget\FaqList;
use MageOS\Faq\Model\Faq;
use MageOS\Faq\Model\Faq\Collector;
use MageOS\Faq\Model\ResourceModel\Faq as FaqResource;
use MageOS\Faq\Setup\Patch\Data\NormalizeFaqIdentifiers;
use PHPUnit\Framework\TestCase;

/**
 * FAQ group identifiers: what may be saved, what old content still finds, and what the upgrade
 * rewrites (issues #1, #2 and #4).
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class FaqIdentifierTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        Bootstrap::getObjectManager()->get(Collector::class)->_resetState();
    }

    /**
     * Issue #1: an identifier outside lowercase letters, digits, - and _ is refused wherever it is
     * saved from, so it never reaches a cache tag.
     *
     * @return void
     */
    public function testAnIdentifierOutsideTheAllowedCharactersIsRefused(): void
    {
        foreach (["shipping\r\nX-Injected: yes", 'faq[', 'returns(', 'Shipping', 'shipping & returns'] as $identifier) {
            $refused = false;
            try {
                $this->saveFaq($identifier);
            } catch (\Exception) {
                $refused = true;
            }
            $this->assertTrue($refused, json_encode($identifier) . ' was saved.');
        }
    }

    /**
     * Issue #2: a numeric group renders its list and its FAQPage structured data.
     *
     * @return void
     */
    public function testANumericGroupRendersItsListAndStructuredData(): void
    {
        $this->saveFaq('123');

        $list = $this->list('123');
        $json = $this->layout()->createBlock(FaqJsonLd::class)
            ->setTemplate('MageOS_Faq::faq/json-ld.phtml')
            ->toHtml();

        $this->assertStringContainsString('Question in 123', $list);
        $this->assertStringContainsString('"@type":"FAQPage"', $json);
    }

    /**
     * Issue #4: content placed before the identifiers were restricted — here as Page Builder stored
     * it, entity-encoded — finds the group the upgrade rewrote its identifier to.
     *
     * @return void
     */
    public function testOldContentFindsItsRewrittenGroup(): void
    {
        $this->saveFaq('shipping-returns');

        $this->assertStringContainsString('Question in shipping-returns', $this->list('shipping &amp; returns'));
        $this->assertStringContainsString('Question in shipping-returns', $this->list('Shipping & Returns'));
    }

    /**
     * The upgrade rewrites the identifiers the rules now refuse, and leaves valid ones alone.
     *
     * @return void
     */
    public function testTheUpgradeRewritesIdentifiersOutsideTheAllowedCharacters(): void
    {
        $ids = [];
        foreach (['Shipping & Returns', 'returns(', '-7', 'valid_one', '!!!'] as $identifier) {
            $ids[$identifier] = $this->insertAsBefore($identifier);
        }

        Bootstrap::getObjectManager()->create(NormalizeFaqIdentifiers::class)->apply();

        $repository = Bootstrap::getObjectManager()->get(FaqRepositoryInterface::class);
        $this->assertSame('shipping-returns', $repository->getById($ids['Shipping & Returns'])->getIdentifier());
        $this->assertSame('returns', $repository->getById($ids['returns('])->getIdentifier());
        $this->assertSame('-7', $repository->getById($ids['-7'])->getIdentifier());
        $this->assertSame('valid_one', $repository->getById($ids['valid_one'])->getIdentifier());
        $this->assertMatchesRegularExpression(
            '/^group-[0-9a-f]{8}$/',
            $repository->getById($ids['!!!'])->getIdentifier()
        );
    }

    /**
     * Save an active FAQ in a group, for every store view.
     *
     * @param string $identifier
     * @return int
     */
    private function saveFaq(string $identifier): int
    {
        $faq = Bootstrap::getObjectManager()->create(Faq::class);
        $faq->setIdentifier($identifier);
        $faq->setQuestion('Question in ' . $identifier);
        $faq->setAnswer('Answer in ' . $identifier);
        $faq->setStoreId(0);
        $faq->setIsActive(true);

        return (int) Bootstrap::getObjectManager()->get(FaqRepositoryInterface::class)->save($faq)->getEntityId();
    }

    /**
     * Store a FAQ row as versions before the restriction could, bypassing today's rules.
     *
     * @param string $identifier
     * @return int
     */
    private function insertAsBefore(string $identifier): int
    {
        $resource   = Bootstrap::getObjectManager()->get(FaqResource::class);
        $connection = $resource->getConnection();
        $connection->insert($resource->getMainTable(), [
            'identifier' => $identifier,
            'store_id'   => 0,
            'question'   => 'Question in ' . $identifier,
            'answer'     => 'Answer in ' . $identifier,
            'is_active'  => 1,
        ]);

        return (int) $connection->lastInsertId($resource->getMainTable());
    }

    /**
     * The FAQ list widget's HTML for a group identifier as content would carry it.
     *
     * @param string $identifier
     * @return string
     */
    private function list(string $identifier): string
    {
        return (string) $this->layout()->createBlock(FaqList::class, '', ['data' => ['identifier' => $identifier]])
            ->toHtml();
    }

    /**
     * @return LayoutInterface
     */
    private function layout(): LayoutInterface
    {
        return Bootstrap::getObjectManager()->get(LayoutInterface::class);
    }
}
