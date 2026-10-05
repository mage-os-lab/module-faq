<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration\Model;

use Magento\Framework\View\LayoutInterface;
use Magento\PageCache\Model\Cache\Type as FullPageCache;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Block\FaqJsonLd;
use MageOS\Faq\Block\Widget\FaqList;
use MageOS\Faq\Model\Faq;
use MageOS\Faq\Model\Faq\Collector;
use PHPUnit\Framework\TestCase;

/**
 * Issue #3: saving a FAQ purges the cached pages showing its group, and no others.
 *
 * Each page's cache tags are the FAQ blocks' own identities, as the full page cache collects them,
 * and the purge is core's: the save's clean_cache_by_tags event, handled by the built-in cache.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoCache full_page enabled
 */
class FaqPagePurgeTest extends TestCase
{
    /**
     * @return void
     */
    public function testSavingAFaqPurgesOnlyThePagesShowingItsGroup(): void
    {
        $shippingFaq = $this->saveFaq('shipping');
        $this->saveFaq('returns');

        $cache = Bootstrap::getObjectManager()->get(FullPageCache::class);
        $cache->save('shipping page', 'mageos_faq_purge_shipping', $this->pageTags('shipping'));
        $cache->save('returns page', 'mageos_faq_purge_returns', $this->pageTags('returns'));
        $cache->save('page without FAQs', 'mageos_faq_purge_none', $this->pageTags(null));

        $shippingFaq->setAnswer('A new answer.');
        Bootstrap::getObjectManager()->get(FaqRepositoryInterface::class)->save($shippingFaq);

        $this->assertFalse($cache->load('mageos_faq_purge_shipping'), 'The shipping page was not purged.');
        $this->assertSame('returns page', $cache->load('mageos_faq_purge_returns'));
        $this->assertSame('page without FAQs', $cache->load('mageos_faq_purge_none'));
    }

    /**
     * The cache tags the FAQ blocks give a page showing a group, or showing none.
     *
     * The FAQPage JSON-LD block is on every page; a page showing a group also has its list.
     *
     * @param string|null $identifier
     * @return string[]
     */
    private function pageTags(?string $identifier): array
    {
        Bootstrap::getObjectManager()->get(Collector::class)->_resetState();
        $layout = Bootstrap::getObjectManager()->get(LayoutInterface::class);

        $tags = [];
        if ($identifier !== null) {
            $list = $layout->createBlock(FaqList::class, '', ['data' => ['identifier' => $identifier]]);
            $list->toHtml();
            $tags = $list->getIdentities();
        }
        $jsonLd = $layout->createBlock(FaqJsonLd::class);

        return array_values(array_unique([...$tags, ...$jsonLd->getIdentities()]));
    }

    /**
     * Save an active FAQ in a group, for every store view.
     *
     * @param string $identifier
     * @return Faq
     */
    private function saveFaq(string $identifier): Faq
    {
        $faq = Bootstrap::getObjectManager()->create(Faq::class);
        $faq->setIdentifier($identifier);
        $faq->setQuestion('Question in ' . $identifier);
        $faq->setAnswer('Answer in ' . $identifier);
        $faq->setStoreId(0);
        $faq->setIsActive(true);
        Bootstrap::getObjectManager()->get(FaqRepositoryInterface::class)->save($faq);

        return $faq;
    }
}
