<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration;

use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Widget\Block\Adminhtml\Widget\Options;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Block\Widget\FaqList;
use MageOS\Faq\Model\Faq;
use PHPUnit\Framework\TestCase;

/**
 * Review low item: the FAQ List widget's group is picked from the groups that exist.
 *
 * Read as the widget dialog reads it: core's options form for the widget, which builds a select's
 * options from the parameter's source model when it adds the field. The merged widget
 * configuration alone carries only the source model's class.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class WidgetGroupPickerTest extends TestCase
{
    /**
     * @return void
     */
    public function testTheGroupIsASelectOfTheExistingGroups(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Faq $faq */
        $faq = $objectManager->create(Faq::class);
        $faq->setIdentifier('zz-widget-picker');
        $faq->setStoreId(0);
        $faq->setQuestion('Q');
        $faq->setAnswer('A');
        $faq->setIsActive(true);
        $objectManager->get(FaqRepositoryInterface::class)->save($faq);

        /** @var Options $form */
        $form = $objectManager->get(LayoutInterface::class)->createBlock(Options::class);
        $form->setWidgetType(FaqList::class);
        $form->addFields();
        $field = $form->getForm()->getElement($form->getMainFieldsetHtmlId() . '_identifier');

        $this->assertNotNull($field, 'The widget has a group identifier field.');
        $this->assertSame('select', $field->getType());
        $this->assertContains(
            'zz-widget-picker',
            array_column((array) $field->getData('values'), 'value'),
            'The group just saved is offered.'
        );
    }
}
