<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration\Controller\Adminhtml\Faq;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Review low item, decision D12a: the grid's selection enables the entries selected, and only those.
 *
 * Core's testAclHasAccess / testAclNoAccess run too, because $uri, $resource and $httpMethod are
 * set.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class MassEnableTest extends AbstractBackendController
{
    use CreatesFaqs;

    /**
     * @var string|null
     */
    protected $resource = 'MageOS_Faq::faq';

    /**
     * @var string|null
     */
    protected $uri = 'backend/mageos_faq/faq/massEnable';

    /**
     * @var string|null
     */
    protected $httpMethod = HttpRequest::METHOD_POST;

    /**
     * @return void
     */
    public function testTheSelectedEntriesAreEnabled(): void
    {
        $selected = [$this->faq(false), $this->faq(false)];
        $other    = $this->faq(false);

        $this->postSelection($selected);

        $this->assertRedirect($this->stringContains('mageos_faq/faq/index'));
        $this->assertSessionMessages(
            $this->containsEqual('FAQ entries enabled: 2.'),
            MessageInterface::TYPE_SUCCESS
        );
        foreach ($selected as $entityId) {
            $this->assertTrue($this->isActive($entityId), \sprintf('Selected entry %d is enabled.', $entityId));
        }
        $this->assertFalse($this->isActive($other), 'An entry not selected is left as it was.');
    }
}
