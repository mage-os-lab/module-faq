<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration\Controller\Adminhtml\Faq;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Review low item, decision D12a: the grid's selection disables the entries selected, and only
 * those.
 *
 * Core's testAclHasAccess / testAclNoAccess run too, because $uri, $resource and $httpMethod are
 * set.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class MassDisableTest extends AbstractBackendController
{
    use CreatesFaqs;

    /**
     * @var string|null
     */
    protected $resource = 'MageOS_Faq::faq';

    /**
     * @var string|null
     */
    protected $uri = 'backend/mageos_faq/faq/massDisable';

    /**
     * @var string|null
     */
    protected $httpMethod = HttpRequest::METHOD_POST;

    /**
     * @return void
     */
    public function testTheSelectedEntriesAreDisabled(): void
    {
        $selected = [$this->faq(), $this->faq()];
        $other    = $this->faq();

        $this->postSelection($selected);

        $this->assertRedirect($this->stringContains('mageos_faq/faq/index'));
        $this->assertSessionMessages(
            $this->containsEqual('FAQ entries disabled: 2.'),
            MessageInterface::TYPE_SUCCESS
        );
        foreach ($selected as $entityId) {
            $this->assertFalse($this->isActive($entityId), \sprintf('Selected entry %d is disabled.', $entityId));
        }
        $this->assertTrue($this->isActive($other), 'An entry not selected is left as it was.');
    }
}
