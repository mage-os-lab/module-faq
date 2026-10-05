<?php

declare(strict_types=1);

namespace MageOS\Faq\Block;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Faq\Api\FaqCollectorInterface;
use MageOS\Faq\Model\Faq\Identifier;
use MageOS\Seo\Model\Faq\SourcePool;

/**
 * Base block for any element that renders a FAQ group (widget, Page Builder, custom).
 *
 * Resolves the configured FAQ group identifier to entries via the source pool and registers the
 * identifier with the request-scoped collector so the late FaqJsonLd block can emit matching
 * FAQPage structured data. Subclasses only set the template and (for widgets) the BlockInterface
 * marker; the resolve→collect flow lives here.
 *
 * Implements IdentityInterface so FPC pages rendering this group carry FAQ cache tags
 * and are purged automatically when a FAQ in the group is saved or deleted.
 */
class AbstractFaqElement extends Template implements IdentityInterface
{
    /**
     * @var array<int, array{question: string, answer: string}>|null
     */
    private ?array $resolved = null;

    /**
     * @param Context $context
     * @param SourcePool $sourcePool
     * @param FaqCollectorInterface $collector
     * @param StoreManagerInterface $storeManager
     * @param Identifier $identifier
     * @param mixed[] $data
     */
    public function __construct(
        Context                                $context,
        private readonly SourcePool            $sourcePool,
        private readonly FaqCollectorInterface $collector,
        private readonly StoreManagerInterface $storeManager,
        private readonly Identifier            $identifier,
        array                                  $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * The configured FAQ group identifier, as the group is stored.
     *
     * Content placed before identifiers were restricted can carry one the upgrade has since rewritten
     * (or, from Page Builder, one with HTML entities); Faq\Identifier::normalize() maps it to the
     * group's identifier as stored now.
     *
     * @return string
     */
    public function getFaqIdentifier(): string
    {
        return $this->identifier->normalize((string) $this->getData('identifier'));
    }

    /**
     * Optional heading rendered above the FAQ list.
     *
     * @return string
     */
    public function getHeading(): string
    {
        return trim((string) $this->getData('heading'));
    }

    /**
     * Resolve the FAQ entries for this element and register the identifier for schema parity.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    public function getFaqs(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $identifier = $this->getFaqIdentifier();
        if ($identifier === '') {
            return $this->resolved = [];
        }

        // Register the rendered group so the late FaqJsonLd block emits matching schema.
        $this->collector->collect($identifier);

        $storeId = (int) $this->storeManager->getStore()->getId();
        return $this->resolved = $this->sourcePool->getFaqs($identifier, $storeId);
    }

    /**
     * Render nothing when the group has no FAQ entries.
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if ($this->getFaqs() === []) {
            return '';
        }
        return parent::_toHtml();
    }

    /**
     * @inheritdoc
     *
     * The group's tag only: a save purges it (Model\Faq::getIdentities()). The bare mageos_faq tag
     * every save also carries would make one FAQ edit purge every page showing any FAQ.
     */
    public function getIdentities(): array
    {
        $tag = $this->identifier->tag($this->getFaqIdentifier());

        return $tag === null ? [] : [$tag];
    }
}
