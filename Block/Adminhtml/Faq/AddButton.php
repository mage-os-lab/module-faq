<?php

declare(strict_types=1);

namespace MageOS\Faq\Block\Adminhtml\Faq;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use MageOS\Faq\Block\Adminhtml\Faq\Edit\GenericButton;

class AddButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     *
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        return [
            'label'      => (string) __('Add New FAQ'),
            'on_click'   => \sprintf("location.href = '%s';", $this->getUrl('*/*/new')),
            'class'      => 'primary',
            'sort_order' => 30,
        ];
    }
}
