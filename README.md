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
  - **Page Builder**: drop the native *FAQ* content type (in "Add Content") into any stage.
- **The same markup for both**: theme-agnostic `<details>`/`<summary>`, with no JavaScript.
- **FAQPage JSON-LD that matches what the page shows.**
  - Both placements feed one request-scoped collector, and the JSON-LD block renders at the end of
    the body.
  - So the structured data always matches the visible questions, even under full-page and block
    cache.
- **Cache identities:** FAQ blocks carry them, so pages are purged when a FAQ changes.
- **A FAQ source for MageOS_Seo:** the table is registered as one source in MageOS_Seo's FAQ source
  pool, beside any other module's. The llms documents read their FAQ groups from that pool.

Each question shows the browser's own open/close triangle. Magento's LESS reset hides it on Luma and
Blank (`summary { display: block; }`), so `view/frontend/web/css/source/_module.less` puts it back
for `.mageos-faq__question` only. Hyvä's Tailwind reset keeps it without help. To restyle, override
`.mageos-faq__question` in your theme.

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
- **Cache tags:** `mageos_faq`, and `mageos_faq_group_<identifier>` per group.
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
