<?php

declare(strict_types=1);

namespace MageOS\Faq\Controller\Adminhtml\Faq;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Ui\Component\MassAction\Filter;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Model\ResourceModel\Faq\CollectionFactory;

/**
 * A FAQ grid mass action: the entries selected in the grid, one at a time through the repository.
 *
 * Core's mass-action filter turns the grid's selection, including "select all" under the grid's
 * filters, into the entries' IDs. Each is then loaded and saved or deleted through the repository,
 * as the edit form's are, not from the filter's collection: a model loaded by a collection holds
 * no stored data, so its save writes back every column, updated_at too, which freezes it. The
 * model's events still fire, which purge the cached pages and queue the feeds' rebuild.
 */
abstract class AbstractMassAction extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Faq::faq';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param FaqRepositoryInterface $faqRepository
     */
    public function __construct(
        Context                                   $context,
        private readonly Filter                   $filter,
        private readonly CollectionFactory        $collectionFactory,
        protected readonly FaqRepositoryInterface $faqRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Apply the action to each selected entry, and say how many it applied to and which failed.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('*/*/');

        try {
            $entityIds = $this->filter->getCollection($this->collectionFactory->create())->getAllIds();
        } catch (LocalizedException $e) {
            // Nothing selected, or a selection the grid could not resolve.
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect;
        }

        $applied = 0;
        foreach ($entityIds as $entityId) {
            try {
                $this->apply((int) $entityId);
                $applied++;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    (string) __('FAQ entry %1: %2', $entityId, $e->getMessage())
                );
            }
        }

        if ($applied > 0) {
            $this->messageManager->addSuccessMessage((string) $this->appliedMessage($applied));
        }

        return $resultRedirect;
    }

    /**
     * Apply the action to one entry.
     *
     * @param int $entityId
     * @throws \Exception The entry is gone, or the repository could not save or delete it.
     * @return void
     */
    abstract protected function apply(int $entityId): void;

    /**
     * The message for the entries the action applied to.
     *
     * @param int $count
     * @return Phrase
     */
    abstract protected function appliedMessage(int $count): Phrase;
}
