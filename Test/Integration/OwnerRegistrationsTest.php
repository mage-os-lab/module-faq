<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration;

use Magento\Backend\Model\Menu\Config as MenuConfig;
use Magento\Framework\Acl\Builder as AclBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\PageBuilder\Model\ConfigInterface as PageBuilderConfig;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Widget\Model\Widget;
use MageOS\Faq\Model\Faq;
use MageOS\Faq\Model\ResourceModel\Faq as FaqResource;
use PHPUnit\Framework\TestCase;

/**
 * The identifiers this module owns, as Magento sees them once the configuration is merged: the
 * `mageos_faq` table, model events and cache tag, the `MageOS_Faq::faq` ACL resource and menu item,
 * the widget and the Page Builder content type. A store's data, admin roles, themes and other modules
 * refer to these, so each is pinned here.
 *
 * @magentoAppArea adminhtml
 */
class OwnerRegistrationsTest extends TestCase
{
    /**
     * The resource sits under MageOS_Seo's SEO resource, where admin roles already find it.
     *
     * @return void
     */
    public function testTheAclResourceIsRegisteredUnderSeo(): void
    {
        $acl = Bootstrap::getObjectManager()->get(AclBuilder::class)->getAcl();

        $this->assertTrue($acl->hasResource('MageOS_Faq::faq'));
        $this->assertTrue($acl->inheritsResource('MageOS_Faq::faq', 'MageOS_Seo::seo'));
        $this->assertFalse($acl->hasResource('MageOS_Seo::faq'));
    }

    /**
     * @return void
     */
    public function testTheFaqMenuItemOpensTheFaqAdmin(): void
    {
        $menu = Bootstrap::getObjectManager()->get(MenuConfig::class)->getMenu();

        $this->assertSame('mageos_faq/faq/index', $menu->get('MageOS_Faq::faq')?->getAction());
        $this->assertNull($menu->get('MageOS_Seo::faq'));
    }

    /**
     * @return void
     */
    public function testTheFaqTableModelEventsAndCacheTagCarryTheFaqPrefix(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $resource      = $objectManager->get(ResourceConnection::class);

        $this->assertSame(
            $resource->getTableName('mageos_faq'),
            $objectManager->get(FaqResource::class)->getMainTable()
        );
        $this->assertTrue($resource->getConnection()->isTableExists($resource->getTableName('mageos_faq')));

        /** @var Faq $faq */
        $faq = $objectManager->create(Faq::class);
        $this->assertSame('mageos_faq', $faq->getEventPrefix());
        $this->assertSame(['mageos_faq'], $faq->getIdentities());
    }

    /**
     * @return void
     */
    public function testTheFaqWidgetIsRegisteredUnderTheFaqPrefix(): void
    {
        $widgets = Bootstrap::getObjectManager()->get(Widget::class)->getWidgets();

        $this->assertArrayHasKey('mageos_faq_list', $widgets);
        $this->assertArrayNotHasKey('mageos_seo_faq_list', $widgets);
    }

    /**
     * The FAQ element sits with core's content elements; this module adds no menu section.
     *
     * @return void
     */
    public function testTheFaqPageBuilderElementIsInAddContent(): void
    {
        if (!interface_exists(PageBuilderConfig::class)) {
            $this->markTestSkipped('Magento_PageBuilder is not installed.');
        }
        $config = Bootstrap::getObjectManager()->get(PageBuilderConfig::class);
        $types  = $config->getContentTypes();

        $this->assertSame('add_content', $types['mageos_faq']['menu_section'] ?? null);
        $this->assertArrayNotHasKey('mageos_seo_faq', $types);
        $this->assertArrayNotHasKey('mageos_seo', $config->getMenuSections());
    }
}
