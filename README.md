# MageOS_Faq

FAQ entries for Magento Open Source and Mage-OS: an admin FAQ Manager, a theme-agnostic widget, a
native Page Builder content type, and matching **FAQPage** structured data.

This module was part of [mage-os/module-seo](https://github.com/mage-os-lab/module-seo)
(MageOS_Seo) on and before 2026-10-02. Its history up to then is kept in that repository.

---

## What it does

- **FAQ Manager** under **Marketing → SEO → FAQ Manager**: FAQ entries grouped by an identifier,
  one row per entry per store view (store view 0 = all store views).
- **Two placements** that render a group:
  - **Widget**: add the *SEO FAQ List* widget to any CMS block or page, or to a layout. Works in
    any theme.
  - **Page Builder**: drop the native *FAQ* content type (in "Add Content") into any stage. It is
    stored as that same widget — see [The Page Builder element](#the-page-builder-element).
- **The same markup for both**: theme-agnostic `<details>`/`<summary>`, with no JavaScript.
- **FAQPage JSON-LD that matches what the page shows.**
  - Both placements feed one request-scoped collector, and the JSON-LD block renders at the end of
    the body.
  - So the structured data matches the visible questions, under the full page cache too. The
    exception is a block that caches its own HTML around them: see [Block cache](#block-cache).
- **Cache identities:** a page carries the cache tag of each FAQ group it shows, so saving or
  deleting a FAQ purges the pages showing its group, and no others.
- **A FAQ source for MageOS_Seo:** the table is registered as one source in MageOS_Seo's FAQ source
  pool, beside any other module's. The llms documents read their FAQ groups from that pool.

Each question shows the browser's own open/close triangle. Magento's LESS reset hides it on Luma and
Blank (`summary { display: block; }`), so `view/frontend/web/css/source/_module.less` puts it back
for `.mageos-faq__question` only. Hyvä's Tailwind reset keeps it without help. To restyle, override
`.mageos-faq__question` in your theme.

### Group identifiers

A group identifier is **lowercase letters, digits, `-` and `_`**, up to 128 characters:
`shipping`, `returns-eu`, `faq_2`. A FAQ with any other identifier is refused when it is saved,
whether from the admin or from code through the repository or the model (`Model\Faq::beforeSave()`),
and both admin forms check the rule as you type.

The identifier ends up where other characters have a meaning of their own:

- **The group's cache tag**, `mageos_faq_group_<identifier>`, which Magento sends Varnish in a
  purge header and a ban expression. A line break there adds a header; a bracket breaks the
  expression.
- **The `{{widget}}` directive** the widget and the Page Builder element are stored as.
- **Lowercase only**, because the database compares identifiers without regard to case:
  `Shipping` and `shipping` were always one group, and their cache tags must agree.

**Upgrading from 1.0.0:** `setup:upgrade` rewrites any identifier outside these characters
(`Setup\Patch\Data\NormalizeFaqIdentifiers`). It lowercases it, decodes HTML entities, turns each
run of other characters into one `-`, and trims `-` from the ends: `Shipping & Returns` becomes
`shipping-returns`. An identifier with nothing left becomes `group-` and eight hex digits. Two
groups that come out alike become one. Each rewrite is logged to `var/log/system.log` with the
FAQ's id and both identifiers.

Content placed before the upgrade keeps working. Widgets, Page Builder elements and settings that
name the old identifier are looked up through the same rewrite (`Model\Faq\Identifier::normalize()`),
so they find the group under its new identifier. Edit them to the new identifier when convenient.
A multi-select that lists FAQ groups, such as MageOS_Aeo's llms.txt *FAQ Groups*, shows an old
identifier as unselected; reselect the group there before saving that page again.

### Block cache

**Don't place the widget or the Page Builder element inside a block that caches its own HTML**:
one with a `cache_lifetime`, or a `ttl` in layout XML. When that block's cache answers, nothing
inside it runs, the FAQ list included:

- the page gets **no FAQPage JSON-LD** for that group, because the list never told the collector
  it was shown;
- the page carries **no cache tag** for the group, so a FAQ edit doesn't purge it;
- the block shows the **old questions** until its own cache expires, even after the full page
  cache is cleared.

Core's CMS blocks, CMS widgets and CMS pages don't cache their HTML, so content placed through
them — the widget, Page Builder, a CMS block in a layout — is not affected. A theme or custom block
that sets a lifetime is. Remove its lifetime, or keep the FAQ outside it.

### The Page Builder element

The FAQ element stores its settings the way core's Block and Products content types store theirs:
as a `{{widget}}` directive inside its div, which core's widget filter renders on the storefront.

```html
<div data-content-type="mageos_faq" data-appearance="default" data-element="main">{{widget type="MageOS\Faq\Block\Widget\FaqList" identifier="shipping" heading="Tips &amp; tricks"}}</div>
```

So it renders wherever Page Builder content does — CMS pages and blocks, category and product
descriptions — with no plugin of this module's in the way.

A directive value cannot carry everything a text field can, so the element's form refuses what it
cannot:

- **The identifier** follows the [group identifier](#group-identifiers) rule.
- **The heading refuses `"`, `{`, `}`, `\` and `%`.** A `"` would end the value early, `}}` would
  end the directive, and the tokenizer keeps or drops a `\`. A `%` starts an escape: core's
  tokenizer URL-decodes a directive's parameters once, so `%41` would render as `A` and `100%25`
  as `100%`.
- **`&`, `<` and `>` are fine.** They are escaped in the directive and show as typed.

Core decodes every `{{widget}}` directive this way, including the ones the editor's *Insert Widget*
writes. That form is core's and stores a value as typed unless it contains `{` or `}`, so there a
heading with `%` and two hex digits, such as `%41`, renders decoded.

---

## Requirements

- PHP 8.3 – 8.5
- Magento Open Source / Mage-OS **2.4.7 or newer** (`magento/framework ^103.0.7`)
- **MageOS_Seo** (`mage-os/module-seo`).
  - It owns the FAQ source pool this module's table registers with, so the structured data and
    the llms documents read them.
  - Its SEO menu and ACL resource hold the FAQ Manager.
  - Its structured-data switch turns the FAQPage JSON-LD off with the rest.
- `magento/module-page-builder` is optional: it is needed only for the FAQ content type.

---

## Installation

```bash
composer require mage-os/module-faq
bin/magento module:enable MageOS_Faq
bin/magento setup:upgrade
bin/magento cache:flush
```

---

## Admin

| Where | What | ACL resource |
| --- | --- | --- |
| Marketing → SEO → FAQ Manager | The FAQ grid and form (`mageos_faq/faq/*`) | `MageOS_Faq::faq` |

Identifiers this module owns:
- **Table:** `mageos_faq`.
- **Cache tags:** `mageos_faq_group_<identifier>` per group, on the pages that show it. A FAQ's
  save or delete purges its group's tag (both groups', when it moves) and `mageos_faq`, which no
  page carries since 1.0.1.
- **Model events:** `mageos_faq_save_after` / `mageos_faq_delete_after`.
- **Widget id:** `mageos_faq_list`.
- **Page Builder content type:** `mageos_faq`.
- **CSS classes:** `mageos-faq`, `mageos-faq__heading`, `__item`, `__question`, `__answer`.

---

## Extending the module

Everything under `Api/` is marked `@api`: it is the module's contract, and Magento's
backward-compatibility promise covers it and nothing else.

To serve FAQs from somewhere other than this module's table, implement MageOS_Seo's
`FaqSourceProviderInterface` and register it in MageOS_Seo's source pool. This module's table source
is one such registration.

### Listing FAQ entries

`FaqRepositoryInterface::getList()` takes Magento's standard search criteria and returns a page of
entries with the total that matched:

```php
$criteria = $this->searchCriteriaBuilder
    ->addFilter('identifier', 'shipping')
    ->addFilter('is_active', 1)
    ->setSortOrders([$this->sortOrderBuilder->setField('sort_order')->setAscendingDirection()->create()])
    ->setPageSize(20)
    ->setCurrentPage(1)
    ->create();

$results = $this->faqRepository->getList($criteria);
$results->getTotalCount();   // every match, not just this page
$results->getItems();        // \MageOS\Faq\Api\Data\FaqInterface[]
```

To show the FAQs of a group on a page, you don't need this: the source pool and the FAQ widget
already do that, store view by store view. `getList()` is for your own code working with the entries
themselves: an export, an integration, an admin tool.

### Adding fields to an FAQ

`FaqInterface` is extensible. Declare your field in your module's `etc/extension_attributes.xml`, and
it appears on every model through `getExtensionAttributes()`:

```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Api/etc/extension_attributes.xsd">
    <extension_attributes for="MageOS\Faq\Api\Data\FaqInterface">
        <attribute code="helpful_votes" type="int"/>
    </extension_attributes>
</config>
```

```php
$faq->getExtensionAttributes()->getHelpfulVotes();
```

`getExtensionAttributes()` never returns null: the object is created on first read. Filling and
storing your field is your module's job, as with any extension attribute: a plugin on the
repository, or a `<join>` in the declaration. `getList()` runs the join processor, so a joined field
arrives filled in and can be filtered and sorted on.

### Errors

The repository throws Magento's standard exceptions, so a caller can tell a missing record from a
failed write:

| Method | Throws |
|---|---|
| `FaqRepositoryInterface::getById()` | `NoSuchEntityException` |
| `FaqRepositoryInterface::save()` | `CouldNotSaveException` |
| `FaqRepositoryInterface::delete()`, `deleteById()` | `CouldNotDeleteException` (and `NoSuchEntityException` for an unknown ID) |

The database's own error is kept as the exception's `getPrevious()`.

---

## Development

```bash
composer install

# Run all quality gates
composer test

# Or individually
vendor/bin/phpunit -c phpunit.xml.dist --testsuite unit
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes
vendor/bin/phpcs --standard=phpcs.xml.dist
XDEBUG_MODE=coverage vendor/bin/infection --threads=4  # gate: minMsi in infection.json5
```

Integration tests live under `Test/Integration/` and run in CI against a live Magento install via
[`graycoreio/github-actions-magento2`](https://github.com/graycoreio/github-actions-magento2).
They cannot be run locally without a full Magento installation.
