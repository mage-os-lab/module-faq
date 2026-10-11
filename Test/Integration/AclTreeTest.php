<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration;

use Magento\Framework\Acl\AclResource\ProviderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Review low item, decision D11a: the FAQ Manager's permission sits where its menu item is,
 * Marketing → SEO, as core's permissions do.
 *
 * It was under MageOS_Seo's top-level "SEO" node, beside Marketing and Stores, while the menu was
 * already Marketing → SEO. The resource ID does not change, so the grants roles already hold keep
 * working.
 *
 * @magentoAppArea adminhtml
 */
class AclTreeTest extends TestCase
{
    /**
     * @return void
     */
    public function testTheFaqManagerSitsUnderMarketingLikeItsMenu(): void
    {
        $this->assertSame(
            ['Magento_Backend::admin', 'Magento_Backend::marketing', 'MageOS_Seo::seo', 'MageOS_Faq::faq'],
            $this->ancestry('MageOS_Faq::faq')
        );
    }

    /**
     * The resource IDs from the root down to the one given, as the merged ACL tree has them.
     *
     * @param string $resourceId
     * @return string[]
     */
    private function ancestry(string $resourceId): array
    {
        $tree = Bootstrap::getObjectManager()->get(ProviderInterface::class)->getAclResources();

        return $this->find($tree, $resourceId, []) ?? [];
    }

    /**
     * Depth-first search for a resource, carrying the path to it.
     *
     * @param array<int, array<string, mixed>> $nodes
     * @param string $resourceId
     * @param string[] $path
     * @return string[]|null
     */
    private function find(array $nodes, string $resourceId, array $path): ?array
    {
        foreach ($nodes as $node) {
            $here   = $path;
            $here[] = (string) $node['id'];
            if ($node['id'] === $resourceId) {
                return $here;
            }
            $found = $this->find($node['children'] ?? [], $resourceId, $here);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }
}
