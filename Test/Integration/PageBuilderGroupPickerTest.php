<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Model\Faq;
use PHPUnit\Framework\TestCase;

/**
 * Review low item: the Page Builder FAQ element's group is picked from the groups that exist.
 *
 * Read from the element's settings form as core builds it, the field prepared with its options.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class PageBuilderGroupPickerTest extends TestCase
{
    /**
     * @return void
     */
    public function testTheGroupIsASelectOfTheExistingGroups(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Faq $faq */
        $faq = $objectManager->create(Faq::class);
        $faq->setIdentifier('zz-pagebuilder-picker');
        $faq->setStoreId(0);
        $faq->setQuestion('Q');
        $faq->setAnswer('A');
        $faq->setIsActive(true);
        $objectManager->get(FaqRepositoryInterface::class)->save($faq);

        $form  = $objectManager->get(UiComponentFactory::class)->create('pagebuilder_mageos_faq_form');
        $field = $form->getComponent('general')?->getComponent('identifier');
        $this->assertNotNull($field, 'The element has a group identifier field.');
        $field->prepare();
        $config = (array) $field->getData('config');

        $this->assertSame('select', $config['formElement'] ?? null);
        $this->assertContains(
            'zz-pagebuilder-picker',
            array_column((array) ($config['options'] ?? []), 'value'),
            'The group just saved is offered.'
        );
    }
}
