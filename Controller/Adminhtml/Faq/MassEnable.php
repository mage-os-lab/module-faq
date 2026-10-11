<?php

declare(strict_types=1);

namespace MageOS\Faq\Controller\Adminhtml\Faq;

use Magento\Framework\Phrase;

/**
 * Enable the FAQ entries selected in the grid.
 */
class MassEnable extends AbstractMassAction
{
    /**
     * @inheritdoc
     */
    protected function apply(int $entityId): void
    {
        $faq = $this->faqRepository->getById($entityId);
        $faq->setIsActive(true);
        $this->faqRepository->save($faq);
    }

    /**
     * @inheritdoc
     */
    protected function appliedMessage(int $count): Phrase
    {
        return __('FAQ entries enabled: %1.', $count);
    }
}
