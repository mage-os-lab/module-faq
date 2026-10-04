<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration;

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Faq\Api\Data\FaqInterface;
use MageOS\Faq\Api\FaqCollectorInterface;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Model\Faq;
use MageOS\Seo\Model\Faq\SourcePool;
use PHPUnit\Framework\TestCase;

/**
 * This module's registrations, as the merged DI configuration builds them.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class DiWiringTest extends TestCase
{
    /**
     * @return void
     */
    public function testTheFaqCollectorIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(FaqCollectorInterface::class);
        $this->assertInstanceOf(FaqCollectorInterface::class, $instance);
    }

    /**
     * @return void
     */
    public function testTheFaqRepositoryIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(FaqRepositoryInterface::class);
        $this->assertInstanceOf(FaqRepositoryInterface::class, $instance);
    }

    /**
     * The table is a source in MageOS_Seo's pool, which the structured data and the llms documents
     * read: an entry saved here comes back through the pool.
     *
     * @return void
     */
    public function testTheTableIsAFaqSourceInSeosPool(): void
    {
        /** @var FaqInterface $faq */
        $faq = Bootstrap::getObjectManager()->create(Faq::class);
        $faq->setIdentifier('di-wiring-check')
            ->setStoreId(0)
            ->setQuestion('Does the pool read the table?')
            ->setAnswer('It does.')
            ->setIsActive(true);
        Bootstrap::getObjectManager()->get(FaqRepositoryInterface::class)->save($faq);

        $pool = Bootstrap::getObjectManager()->create(SourcePool::class);

        $this->assertContains('di-wiring-check', $pool->getIdentifiers());
        $this->assertSame(
            [['question' => 'Does the pool read the table?', 'answer' => 'It does.']],
            $pool->getFaqs('di-wiring-check', 1)
        );
    }
}
