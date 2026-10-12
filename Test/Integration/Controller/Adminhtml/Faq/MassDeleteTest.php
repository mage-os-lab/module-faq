<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration\Controller\Adminhtml\Faq;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;
use MageOS\Faq\Api\FaqRepositoryInterface;

/**
 * Review low item, decision D12a: the grid's selection deletes the entries selected, and only those.
 *
 * Core's testAclHasAccess / testAclNoAccess run too, because $uri, $resource and $httpMethod are
 * set.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class MassDeleteTest extends AbstractBackendController
{
    use CreatesFaqs;

    /**
     * @var string|null
     */
    protected $resource = 'MageOS_Faq::faq';

    /**
     * @var string|null
     */
    protected $uri = 'backend/mageos_faq/faq/massDelete';

    /**
     * @var string|null
     */
    protected $httpMethod = HttpRequest::METHOD_POST;

    /**
     * @return void
     */
    public function testTheSelectedEntriesAreDeleted(): void
    {
        $selected = [$this->faq(), $this->faq()];
        $kept     = $this->faq();

        $this->postSelection($selected);

        $this->assertRedirect($this->stringContains('mageos_faq/faq/index'));
        $this->assertSessionMessages(
            $this->containsEqual('FAQ entries deleted: 2.'),
            MessageInterface::TYPE_SUCCESS
        );
        $repository = $this->_objectManager->create(FaqRepositoryInterface::class);
        $this->assertSame($kept, $repository->getById($kept)->getEntityId(), 'An entry not selected is kept.');
        foreach ($selected as $entityId) {
            try {
                $repository->getById($entityId);
                $this->fail(\sprintf('Selected entry %d was not deleted.', $entityId));
            } catch (NoSuchEntityException) {
                // Deleted, as selected.
            }
        }
    }
}
