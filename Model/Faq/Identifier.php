<?php

declare(strict_types=1);

namespace MageOS\Faq\Model\Faq;

use MageOS\Faq\Model\Faq;

/**
 * What a FAQ group identifier may be, and the cache tag it becomes.
 *
 * Lowercase letters, digits, - and _, up to 128 characters (the column's length). An identifier ends
 * up in places that give other characters a meaning of their own: the cache tag Magento sends
 * Varnish in a purge header and a ban expression (a line break adds a header, a bracket breaks the
 * expression — issue #1), and the {{widget}} directive Page Builder stores (issue #4). Lowercase
 * only, because the database matches identifiers without regard to case: "Shipping" and "shipping"
 * were already one group, and their cache tags must agree.
 *
 * Saving refuses anything else (Model\Faq::beforeSave()). Content placed before the restriction —
 * widgets, Page Builder elements, the FAQ groups other modules are configured with — is looked up
 * through normalize(), which turns it into the identifier the upgrade rewrote its group to
 * (Setup\Patch\Data\NormalizeFaqIdentifiers), so it keeps finding it.
 *
 * Injected wherever the rule applies: the model, both FAQ blocks, Faq\GroupReader and the upgrade.
 * A preference or plugin here changes the rule for all of them, but not the admin forms' check,
 * which is the mageos-faq-identifier rule in view/adminhtml/web/js/validation/identifier-mixin.js.
 */
class Identifier
{
    private const VALID = '/^[a-z0-9_-]{1,128}$/';

    private const MAX_LENGTH = 128;

    /**
     * Whether a value is a valid identifier as it stands.
     *
     * @param string $identifier
     * @return bool
     */
    public function isValid(string $identifier): bool
    {
        return preg_match(self::VALID, $identifier) === 1;
    }

    /**
     * The identifier a value stands for: itself when valid, otherwise as the upgrade rewrote it.
     *
     * HTML entities are decoded (Page Builder stored them encoded), letters are lowercased, every run
     * of other characters becomes one -, and - is trimmed from the ends. A value with nothing left
     * becomes group-<hash>, the same for the same value. An empty value stays empty: no group.
     *
     * @param string $value
     * @return string
     */
    public function normalize(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || $this->isValid($trimmed)) {
            return $trimmed;
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged -- undoes Page Builder's entities
        $text       = strtolower(html_entity_decode($trimmed, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $identifier = trim((string) preg_replace('/[^a-z0-9_-]+/', '-', $text), '-');
        $identifier = trim(substr($identifier, 0, self::MAX_LENGTH), '-');
        if (!$this->isValid($identifier)) {
            return 'group-' . substr(sha1($trimmed), 0, 8);
        }

        return $identifier;
    }

    /**
     * The cache tag of a group's pages, or null when the identifier is not valid.
     *
     * @param string $identifier
     * @return string|null
     */
    public function tag(string $identifier): ?string
    {
        return $this->isValid($identifier) ? Faq::CACHE_TAG . '_group_' . $identifier : null;
    }
}
