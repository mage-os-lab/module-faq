<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration\Controller;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\PageFactory;
use Magento\TestFramework\TestCase\AbstractController;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Block\Widget\FaqList;
use MageOS\Faq\Model\Faq;

/**
 * The FAQ Page Builder content type on the storefront.
 *
 * Page Builder stores the element as a {{widget}} directive inside its div, the way core's Block and
 * Products content types store theirs (view/adminhtml/web/js/content-type/mageos_faq/mass-converter/
 * widget-directive.js writes it). Core's widget filter renders it: the FAQ list and the matching
 * FAQPage JSON-LD, with the heading's & < > escaped in the directive and shown as typed.
 *
 * This pins the stored format the converter writes; core renders it with or without this module's
 * former Filter\Template plugin.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation disabled
 */
class PageBuilderFaqTest extends AbstractController
{
    /**
     * IDs of the CMS pages created by the running test.
     *
     * @var int[]|null
     */
    private ?array $createdPageIds = [];

    /**
     * IDs of the FAQ entries created by the running test.
     *
     * @var int[]|null
     */
    private ?array $createdFaqIds = [];

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $pages = $this->_objectManager->get(PageRepositoryInterface::class);
        foreach ($this->createdPageIds ?? [] as $pageId) {
            try {
                $pages->deleteById($pageId);
            } catch (\Exception) {
                // Already gone.
            }
        }
        $faqs = $this->_objectManager->get(FaqRepositoryInterface::class);
        foreach ($this->createdFaqIds ?? [] as $faqId) {
            try {
                $faqs->deleteById($faqId);
            } catch (\Exception) {
                // Already gone.
            }
        }
        $this->createdPageIds = [];
        $this->createdFaqIds  = [];

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testTheStoredDirectiveRendersTheFaqAndItsStructuredData(): void
    {
        $identifier = 'pb-faq-' . uniqid();
        $this->faq($identifier, 'Do you ship to the EU?', 'Yes, within five working days.');
        $pageId = $this->page(
            '<div data-content-type="mageos_faq" data-appearance="default" data-element="main">'
            . '{{widget type="' . FaqList::class . '" identifier="' . $identifier . '"'
            . ' heading="Tips &amp; tricks &lt;2026&gt;"}}'
            . '</div>'
        );

        $this->dispatch('cms/page/view/page_id/' . $pageId);
        $body = (string) $this->getResponse()->getBody();

        $this->assertStringNotContainsString('{{widget', $body, 'The directive is rendered, not shown.');
        $this->assertStringContainsString(
            '<h2 class="mageos-faq__heading">Tips &amp; tricks &lt;2026&gt;</h2>',
            $body,
            'The heading shows as typed: "Tips & tricks <2026>".'
        );
        $this->assertStringContainsString(
            '<summary class="mageos-faq__question">Do you ship to the EU?</summary>',
            $body
        );
        $this->assertSame(['Do you ship to the EU?'], $this->faqPageQuestions($body));
    }

    /**
     * An active FAQ entry for every store view.
     *
     * @param string $identifier
     * @param string $question
     * @param string $answer
     * @return void
     */
    private function faq(string $identifier, string $question, string $answer): void
    {
        /** @var Faq $faq */
        $faq = $this->_objectManager->create(Faq::class);
        $faq->setIdentifier($identifier);
        $faq->setStoreId(0);
        $faq->setQuestion($question);
        $faq->setAnswer($answer);
        $faq->setSortOrder(0);
        $faq->setIsActive(true);
        $this->_objectManager->get(FaqRepositoryInterface::class)->save($faq);
        $this->createdFaqIds[] = (int) $faq->getEntityId();
    }

    /**
     * An active CMS page in every store view with the given content; returns its ID.
     *
     * @param string $content
     * @return int
     */
    private function page(string $content): int
    {
        $page = $this->_objectManager->get(PageFactory::class)->create();
        $page->setData([
            PageInterface::IDENTIFIER => 'mageos-faq-pb-' . uniqid(),
            PageInterface::TITLE      => 'MageOS FAQ Page Builder',
            PageInterface::CONTENT    => $content,
            PageInterface::IS_ACTIVE  => 1,
            'stores'                  => [0],
        ]);
        $this->_objectManager->get(PageRepositoryInterface::class)->save($page);
        $this->createdPageIds[] = (int) $page->getId();

        return (int) $page->getId();
    }

    /**
     * The questions of the page's FAQPage JSON-LD node.
     *
     * @param string $body
     * @return string[]
     */
    private function faqPageQuestions(string $body): array
    {
        preg_match_all('#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $body, $matches);
        foreach ($matches[1] as $json) {
            $decoded = json_decode($json, true);
            foreach (\is_array($decoded) && array_is_list($decoded) ? $decoded : [$decoded] as $node) {
                if (\is_array($node) && ($node['@type'] ?? null) === 'FAQPage') {
                    return array_map(
                        static fn (array $question): string => (string) ($question['name'] ?? ''),
                        $node['mainEntity'] ?? []
                    );
                }
            }
        }

        return [];
    }
}
