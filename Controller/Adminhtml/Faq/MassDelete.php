<?php

declare(strict_types=1);

namespace MageOS\Faq\Controller\Adminhtml\Faq;

use Magento\Framework\Phrase;

/**
 * Delete the FAQ entries selected in the grid.
 */
class MassDelete extends AbstractMassAction
{
    /**
     * @inheritdoc
     */
    protected function apply(int $entityId): void
    {
        $this->faqRepository->deleteById($entityId);
    }

    /**
     * @inheritdoc
     */
    protected function appliedMessage(int $count): Phrase
    {
        return __('FAQ entries deleted: %1.', $count);
    }
}
