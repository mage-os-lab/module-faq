# Changelog

All notable changes to this module are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow
[Semantic Versioning](https://semver.org/). Releases are cut from git tags —
the tag is the source of truth for the version (composer.json carries no
hardcoded version field).

**Origin.** This module was part of
[mage-os/module-seo](https://github.com/mage-os-lab/module-seo) (MageOS_Seo) on and
before 2026-10-02. Its history up to then is kept in that repository.

## [Unreleased]

## [1.0.0] — 2026-10-05

### Added

- **Split from mage-os/module-seo.** The FAQ entity and table, its repository, the FAQ Manager,
  the widget, the Page Builder content type and the FAQPage JSON-LD come from MageOS_Seo, which this
  module requires.
  - MageOS_Seo keeps the FAQ source pool (`Model\Faq\SourcePool`) and its contract
    (`Api\FaqSourceProviderInterface`). This module's table registers there as one source.
  - **Unchanged:** the table `mageos_faq` and its rows, the ACL resource and menu item
    `MageOS_Faq::faq` (under Marketing → SEO), the admin URLs `mageos_faq/faq/*`, the layout handles
    and UI components, the cache tags, the model events, the widget id, the Page Builder content
    type and the CSS classes.
  - **Breaking: the namespace is `MageOS\Faq\`**, where it was `MageOS\Seo\`. The class names after
    it are unchanged: `Api\FaqRepositoryInterface`, `Api\FaqCollectorInterface`,
    `Api\Data\FaqInterface`, `Api\Data\FaqSearchResultsInterface`, `Model\Faq`, `Model\FaqRepository`,
    `Model\Faq\GroupReader`, `Model\Faq\Collector`, `Model\Faq\Source\TableFaqSource`,
    `Model\ResourceModel\Faq`, `Block\FaqJsonLd`, `Block\AbstractFaqElement`,
    `Block\Widget\FaqList` and the admin blocks, controllers and UI classes. Code that injects them,
    or names them in di.xml or `extension_attributes.xml`, must follow.
  - **Templates are `MageOS_Faq::faq/list.phtml` and `MageOS_Faq::faq/json-ld.phtml`**, and the Page
    Builder templates `MageOS_Faq/content-type/mageos_faq/…`. A theme override of them moves to
    `MageOS_Faq`.
- **`FaqRepositoryInterface::getList(SearchCriteriaInterface)`**, returning the new
  `Api\Data\FaqSearchResultsInterface`: FAQ entries by Magento's standard search criteria, with the
  total that matched. Extension attributes declared with a `<join>` are joined in. See the README's
  "Listing FAQ entries".
- **Extension attributes on `FaqInterface`.** It extends `ExtensibleDataInterface`, so other modules
  add fields through `extension_attributes.xml`. `getExtensionAttributes()` never returns null.
- **`@api` on every interface under `Api/`**, which marks the module's contract, and a unit test
  that fails if an interface there lacks it.

### Changed

- **The Page Builder FAQ element is stored as a `{{widget}}` directive** (review A6), the way
  core's Block and Products content types store theirs.
  - Core's widget filter renders it on the storefront, wherever Page Builder content goes.
  - `Plugin\PageBuilder\FaqRenderer`, the plugin on `Magento\Framework\Filter\Template` that
    replaced a placeholder div on every filtered string, is removed, and with it
    `etc/frontend/di.xml`.
  - The element's form refuses `"`, `{`, `}` and `\` in the identifier and the heading, which a
    directive value cannot carry (rule `mageos-faq-directive-safe`). `&`, `<` and `>` are carried.
  - **FAQ elements saved before this change render nothing** until they are opened in Page
    Builder, given their identifier (and heading) again, and saved. Their settings were in data
    attributes, which the element no longer reads. Nothing is migrated.
  - See the README's "The Page Builder element".
- **Breaking: the FAQ uses its own prefix, `mageos_faq`.** Nothing is migrated.
  - the table `mageos_seo_faq` becomes `mageos_faq`; `setup:upgrade` creates it empty and drops
    the old one with its rows;
  - the ACL resource and menu item `MageOS_Seo::faq` become `MageOS_Faq::faq`, still under
    Marketing → SEO;
  - the admin moves to `mageos_faq/faq/*`, with the layout handles `mageos_faq_faq_index` /
    `_edit` and the UI components `mageos_faq_listing` / `mageos_faq_form`;
  - the cache tag is `mageos_faq` (and `mageos_faq_group_<identifier>`), and the model events
    are `mageos_faq_save_after` / `_delete_after`;
  - the widget id is `mageos_faq_list`;
  - the Page Builder content type is `mageos_faq`, in core's "Add Content" section; the
    "MageOS SEO" section is gone. Page Builder content saved with the old type no longer
    renders;
  - the list markup's CSS classes are `mageos-faq`, `mageos-faq__heading`, `__item`,
    `__question` and `__answer`. A theme that styled `.mageos-seo-faq*` must follow.
- **Breaking: `Model\Faq\Repository` is now `Model\Faq\GroupReader`,** which says what it is: the
  storefront's reader of a FAQ group, behind `TableFaqSource`. `Api\FaqRepositoryInterface` remains
  the repository.
- **`Model\Faq` extends `AbstractExtensibleModel`,** so its constructor takes
  `ExtensionAttributesFactory` and `AttributeValueFactory` after the registry. Code that builds it
  through the object manager is unaffected.
- **`FaqRepository`'s constructor** takes the FAQ collection factory, the extension attributes join
  processor, the search criteria collection processor and the search results factory.
- The FAQ Save and Delete admin controllers no longer take `FeedInvalidator`: saving or deleting a
  FAQ dispatches the model's own events, which the llms documents' rebuild listens for.

### Fixed

- Saving or deleting a FAQ anywhere but the admin form — the REST API, an import, a data patch —
  left `/llms.txt` and `/llms-full.txt` stale until the nightly rebuild: only the admin controllers
  asked for a rebuild. The model now dispatches its own events (`mageos_faq_save_after` /
  `_delete_after`), and those queue it, however the save was made.
- The FAQ form's Group Identifier note sent the admin to "MageOS SEO → SEO → AI Discoverability
  (llms.txt)"; since the settings moved, it is "MageOS SEO → AI Information & Crawlers → AI
  Discoverability (llms.txt)".
- The FAQ widget's questions showed no open/close triangle on Luma and Blank: Magento's LESS reset
  sets `summary { display: block; }`, which hides a `<summary>`'s marker. The module's
  `_module.less` restores `display: list-item` (and a pointer cursor) on `.mageos-faq__question`.
  Hyvä is unaffected.
- The FAQ admin form declares an ACL resource. Its controllers always did, but a UI component's
  data is also reachable through the generic `mui/index/render` endpoint, which checks the
  component's own `aclResource` and not the controller's.
- The FAQ widget no longer sets a block-cache `ttl`, which could FPC-cache pages without their
  FAQPage JSON-LD.
- The FAQ collector implements `ResetAfterRequestInterface`, so its state cannot leak between
  requests under long-lived application servers.
