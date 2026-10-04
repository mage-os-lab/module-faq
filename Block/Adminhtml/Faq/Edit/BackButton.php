<?php

declare(strict_types=1);

namespace MageOS\Faq\Block\Adminhtml\Faq\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class BackButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     *
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        return [
            'label'      => (string) __('Back'),
            'on_click'   => \sprintf("location.href = '%s';", $this->getUrl('*/*/')),
            'class'      => 'back',
            'sort_order' => 10,
        ];
    }
}
