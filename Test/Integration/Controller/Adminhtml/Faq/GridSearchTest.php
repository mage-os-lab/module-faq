<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration\Controller\Adminhtml\Faq;

use Magento\TestFramework\TestCase\AbstractBackendController;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Model\Faq;

/**
 * Review low item: the FAQ grid's keyword search finds the entries that contain the word.
 *
 * The listing has core's keyword search box, whose filter matches against the table's fulltext
 * index; the table had none, and core's filter then returns without filtering, so every search
 * showed every entry. Read as the grid reads it, through mui/index/render, as core's own CMS grid
 * search test does.
 *
 * Database isolation is off: InnoDB adds a row to a fulltext index when its transaction commits,
 * so inside the test's transaction a search would find nothing for that reason alone. The entries
 * are deleted afterwards.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 */
class GridSearchTest extends AbstractBackendController
{
    /**
     * Entries this test created.
     *
     * @var int[]|null
     */
    private ?array $created = [];

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $repository = $this->_objectManager->get(FaqRepositoryInterface::class);
        foreach ($this->created ?? [] as $entityId) {
            $repository->deleteById($entityId);
        }
        $this->created = [];

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testASearchFindsTheEntryContainingTheWord(): void
    {
        $this->faq('Do you ship to Zanzibar?', 'Twice a week.');
        $this->faq('Can I return worn shoes?', 'Within thirty days.');

        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->dispatch('backend/mui/index/render/?namespace=mageos_faq_listing&search=Zanzibar');
        $data = json_decode((string) $this->getResponse()->getBody(), true);

        $this->assertSame(['Do you ship to Zanzibar?'], array_column($data['items'] ?? [], 'question'));
        $this->assertSame(1, (int) ($data['totalRecords'] ?? 0));
    }

    /**
     * Save an active entry for all store views.
     *
     * @param string $question
     * @param string $answer
     * @return void
     */
    private function faq(string $question, string $answer): void
    {
        /** @var Faq $faq */
        $faq = $this->_objectManager->create(Faq::class);
        $faq->setIdentifier('mageos-faq-search-' . uniqid());
        $faq->setStoreId(0);
        $faq->setQuestion($question);
        $faq->setAnswer($answer);
        $faq->setIsActive(true);

        $this->created[] = (int) $this->_objectManager->get(FaqRepositoryInterface::class)->save($faq)->getEntityId();
    }
}
