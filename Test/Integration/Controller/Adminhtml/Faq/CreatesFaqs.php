<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration\Controller\Adminhtml\Faq;

use Magento\Framework\App\Request\Http as HttpRequest;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Model\Faq;

/**
 * Entries for the grid's mass-action tests, and the grid's selection posted as core's mass-action
 * filter reads it.
 *
 * Expects to be used in a Magento\TestFramework\TestCase\AbstractBackendController.
 */
trait CreatesFaqs
{
    /**
     * Save an entry for all store views and return its ID.
     *
     * @param bool $isActive
     * @return int
     */
    private function faq(bool $isActive = true): int
    {
        /** @var Faq $faq */
        $faq = $this->_objectManager->create(Faq::class);
        $faq->setIdentifier('mageos-faq-mass-' . uniqid());
        $faq->setStoreId(0);
        $faq->setQuestion('Q');
        $faq->setAnswer('A');
        $faq->setIsActive($isActive);

        return (int) $this->_objectManager->get(FaqRepositoryInterface::class)->save($faq)->getEntityId();
    }

    /**
     * Post the grid's selection to this test's URI.
     *
     * @param int[] $entityIds
     * @return void
     */
    private function postSelection(array $entityIds): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue([
            'selected'  => array_map('strval', $entityIds),
            'namespace' => 'mageos_faq_listing',
        ]);
        $this->dispatch($this->uri);
    }

    /**
     * An entry's Active flag as stored.
     *
     * @param int $entityId
     * @return bool
     */
    private function isActive(int $entityId): bool
    {
        return $this->_objectManager->create(FaqRepositoryInterface::class)->getById($entityId)->getIsActive();
    }
}
