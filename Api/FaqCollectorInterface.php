<?php

declare(strict_types=1);

namespace MageOS\Faq\Api;

/**
 * Request-scoped registry of FAQ group identifiers rendered on the current page.
 *
 * Visible FAQ elements (widget, Page Builder, any AbstractFaqElement) register their group
 * identifier as they render in the body; a late head/end-of-body block re-resolves those
 * identifiers to emit FAQPage structured data that matches what is actually shown. An element
 * registers only when it runs: inside a block that serves its own HTML from the block cache, it
 * does not, and the page gets no FAQPage data for it (README, "Block cache").
 *
 * @api
 */
interface FaqCollectorInterface
{
    /**
     * Record that a FAQ group identifier has been rendered on the page.
     *
     * @param string $identifier
     * @return void
     */
    public function collect(string $identifier): void;

    /**
     * Return the distinct collected group identifiers, in first-seen order.
     *
     * @return string[]
     */
    public function getIdentifiers(): array;
}
