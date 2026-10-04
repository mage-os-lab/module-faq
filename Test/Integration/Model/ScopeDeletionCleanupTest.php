<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\ResourceModel\Website as WebsiteResource;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\WebsiteFactory;
use Magento\Store\Test\Fixture\Group as GroupFixture;
use Magento\Store\Test\Fixture\Store as StoreFixture;
use Magento\Store\Test\Fixture\Website as WebsiteFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Faq\Api\Data\FaqInterface;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Model\Faq;
use PHPUnit\Framework\TestCase;

/**
 * A store view's FAQ entries go with it: mageos_faq.store_id carries a foreign key with ON DELETE
 * CASCADE.
 *
 * Deleting a website is the case worth the setup: core removes its store views with a database-level
 * cascade that dispatches no store_delete event, so nothing in PHP ever hears about them.
 *
 * Database isolation is disabled because creating websites and store views is not transactional.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 */
class ScopeDeletionCleanupTest extends TestCase
{
    /**
     * @return void
     */
    #[DataFixture(StoreFixture::class, as: 'store')]
    public function testDeletingAStoreViewRemovesItsFaqEntries(): void
    {
        $storeId = (int) $this->fixture('store')->getId();
        $faqId   = $this->saveFaq($storeId);

        $this->deleteStore($storeId);

        $this->assertFaqIsGone($faqId);
    }

    /**
     * The case no observer can see: the store views go with the website, in the database.
     *
     * @return void
     */
    #[DataFixture(WebsiteFixture::class, as: 'website')]
    #[DataFixture(GroupFixture::class, ['website_id' => '$website.id$'], 'group')]
    #[DataFixture(StoreFixture::class, ['store_group_id' => '$group.id$'], 'store')]
    public function testDeletingAWebsiteRemovesTheFaqEntriesOfItsStoreViews(): void
    {
        $faqId = $this->saveFaq((int) $this->fixture('store')->getId());

        $this->deleteWebsite((int) $this->fixture('website')->getId());

        $this->assertFaqIsGone($faqId);
    }

    /**
     * Save a FAQ entry for one store view and return its ID.
     *
     * @param int $storeId
     * @return int
     */
    private function saveFaq(int $storeId): int
    {
        /** @var FaqInterface $faq */
        $faq = Bootstrap::getObjectManager()->create(Faq::class);
        $faq->setIdentifier('scope-deletion-check')
            ->setStoreId($storeId)
            ->setQuestion('Does this record survive its store view?')
            ->setAnswer('It should not.')
            ->setIsActive(true);
        Bootstrap::getObjectManager()->get(FaqRepositoryInterface::class)->save($faq);

        return $faq->getEntityId();
    }

    /**
     * Assert that the FAQ entry no longer exists.
     *
     * @param int $faqId
     * @return void
     */
    private function assertFaqIsGone(int $faqId): void
    {
        try {
            Bootstrap::getObjectManager()->get(FaqRepositoryInterface::class)->getById($faqId);
            $this->fail('The FAQ entry went with the store view.');
        } catch (NoSuchEntityException) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * Delete a store view through its resource, as the admin controller does.
     *
     * @param int $storeId
     * @return void
     */
    private function deleteStore(int $storeId): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $resource      = $objectManager->get(StoreResource::class);

        $store = $objectManager->get(StoreFactory::class)->create();
        $resource->load($store, $storeId);
        $resource->delete($store);
        $objectManager->get(StoreManagerInterface::class)->reinitStores();
    }

    /**
     * Delete a website through its resource; core cascades its groups and store views.
     *
     * @param int $websiteId
     * @return void
     */
    private function deleteWebsite(int $websiteId): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $resource      = $objectManager->get(WebsiteResource::class);

        $website = $objectManager->get(WebsiteFactory::class)->create();
        $resource->load($website, $websiteId);
        $resource->delete($website);
        $objectManager->get(StoreManagerInterface::class)->reinitStores();
    }

    /**
     * An entity created by a data fixture.
     *
     * @param string $name
     * @return \Magento\Framework\DataObject
     */
    private function fixture(string $name): \Magento\Framework\DataObject
    {
        return DataFixtureStorageManager::getStorage()->get($name);
    }
}
