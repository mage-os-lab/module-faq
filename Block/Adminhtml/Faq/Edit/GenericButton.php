<?php

declare(strict_types=1);

namespace MageOS\Faq\Block\Adminhtml\Faq\Edit;

use Magento\Backend\Block\Widget\Context;

/**
 * Shared helper for FAQ form buttons.
 */
class GenericButton
{
    /**
     * @param Context $context
     */
    public function __construct(
        protected readonly Context $context
    ) {
    }

    /**
     * Current FAQ entity ID from the request, or null for a new entry.
     *
     * @return int|null
     */
    public function getFaqId(): ?int
    {
        $id = (int) $this->context->getRequest()->getParam('entity_id');
        return $id !== 0 ? $id : null;
    }

    /**
     * Build an admin URL.
     *
     * @param string $route
     * @param array<string,mixed> $params
     * @return string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
