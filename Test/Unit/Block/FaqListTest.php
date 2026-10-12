<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Unit\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Faq\Api\FaqCollectorInterface;
use MageOS\Faq\Block\Widget\FaqList;
use MageOS\Faq\Model\Faq\Identifier;
use MageOS\Seo\Api\FaqSourcePoolInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the AbstractFaqElement resolve/collect flow through the concrete FaqList widget.
 */
class FaqListTest extends TestCase
{
    /**
     * @var FaqSourcePoolInterface&Stub
     */
    private FaqSourcePoolInterface&Stub $sourcePool;

    /**
     * @var FaqCollectorInterface&Stub
     */
    private FaqCollectorInterface&Stub $collector;

    /**
     * @var StoreManagerInterface&Stub
     */
    private StoreManagerInterface&Stub $storeManager;

    protected function setUp(): void
    {
        $this->sourcePool = $this->createStub(FaqSourcePoolInterface::class);
        $this->collector  = $this->createStub(FaqCollectorInterface::class);

        $this->storeManager = $this->createStub(StoreManagerInterface::class);
        $store              = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $this->storeManager->method('getStore')->willReturn($store);
    }

    /**
     * The widget under test, over the given doubles or this test's stubs.
     *
     * @param FaqSourcePoolInterface|null $sourcePool
     * @param FaqCollectorInterface|null $collector
     * @return FaqList
     */
    private function block(
        ?FaqSourcePoolInterface $sourcePool = null,
        ?FaqCollectorInterface $collector = null
    ): FaqList {
        return new FaqList(
            $this->createStub(Context::class),
            $sourcePool ?? $this->sourcePool,
            $collector ?? $this->collector,
            $this->storeManager,
            new Identifier()
        );
    }

    public function testResolvesFaqsAndCollectsIdentifier(): void
    {
        $faqs = [['question' => 'Q', 'answer' => 'A']];
        $collector = $this->createMock(FaqCollectorInterface::class);
        $collector->expects($this->once())->method('collect')->with('shipping');
        $sourcePool = $this->createMock(FaqSourcePoolInterface::class);
        $sourcePool->method('getFaqs')->with('shipping', 1)->willReturn($faqs);
        $block = $this->block($sourcePool, $collector);
        $block->setData('identifier', 'shipping');

        $this->assertSame($faqs, $block->getFaqs());
    }

    public function testEmptyIdentifierReturnsEmptyAndDoesNotCollect(): void
    {
        $collector = $this->createMock(FaqCollectorInterface::class);
        $collector->expects($this->never())->method('collect');
        $block = $this->block(collector: $collector);
        $block->setData('identifier', '');
        $this->assertSame([], $block->getFaqs());
    }

    public function testResolutionIsMemoised(): void
    {
        $sourcePool = $this->createMock(FaqSourcePoolInterface::class);
        $sourcePool->expects($this->once())->method('getFaqs')->willReturn([
            ['question' => 'Q', 'answer' => 'A'],
        ]);
        $block = $this->block($sourcePool);
        $block->setData('identifier', 'shipping');
        $block->getFaqs();
        $block->getFaqs();
    }
}
