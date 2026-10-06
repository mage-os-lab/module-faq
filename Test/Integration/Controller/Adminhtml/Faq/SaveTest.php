<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration\Controller\Adminhtml\Faq;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Model\Faq;
use MageOS\Faq\Model\Faq\GroupReader;

/**
 * Saving the FAQ form stores the entry and returns to the grid; a failed save returns to the form
 * with what was typed.
 *
 * Core's testAclHasAccess / testAclNoAccess run too, because $uri, $resource and $httpMethod are
 * set.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class SaveTest extends AbstractBackendController
{
    /**
     * @var string|null
     */
    protected $resource = 'MageOS_Faq::faq';

    /**
     * @var string|null
     */
    protected $uri = 'backend/mageos_faq/faq/save';

    /**
     * @var string|null
     */
    protected $httpMethod = HttpRequest::METHOD_POST;

    /**
     * @return void
     */
    public function testANewEntryIsSavedAndTheAdminReturnsToTheGrid(): void
    {
        $identifier = 'mageos-faq-admin-' . uniqid();

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue([
            'identifier' => $identifier,
            'store_id'   => '0',
            'question'   => 'Where is my order?',
            'answer'     => 'On its way.',
            'is_active'  => '1',
        ]);
        $this->dispatch($this->uri);

        $this->assertSessionMessages(
            $this->containsEqual('The FAQ entry has been saved.'),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertRedirect($this->stringContains('mageos_faq/faq/'));

        $saved = $this->_objectManager->create(GroupReader::class)->getByIdentifier($identifier, 0);
        $this->assertSame(['Where is my order?'], array_column($saved, 'question'));
        $this->assertSame(['On its way.'], array_column($saved, 'answer'));
    }

    /**
     * Issue #7: a new FAQ whose save fails reopens with what was typed.
     *
     * @return void
     */
    public function testANewFaqWhoseSaveFailsReopensWithItsInput(): void
    {
        $this->post(['identifier' => 'Not Allowed', 'question' => 'Kept after a failed save']);

        $this->assertRedirect($this->logicalNot($this->stringContains('entity_id')));
        $this->assertSame('Kept after a failed save', $this->formData(null)['question'] ?? null);
    }

    /**
     * An existing FAQ whose save fails reopens with what was typed, not what is stored.
     *
     * @return void
     */
    public function testAnExistingFaqWhoseSaveFailsReopensWithItsInput(): void
    {
        $faq = $this->_objectManager->create(Faq::class);
        $faq->setIdentifier('mageos-faq-admin-' . uniqid());
        $faq->setQuestion('Stored question');
        $faq->setAnswer('Stored answer');
        $this->_objectManager->get(FaqRepositoryInterface::class)->save($faq);

        $this->post([
            'entity_id'  => (string) $faq->getEntityId(),
            'identifier' => 'Not Allowed',
            'question'   => 'Kept after a failed save',
        ]);

        $this->assertSame(
            'Kept after a failed save',
            $this->formData($faq->getEntityId())['question'] ?? null
        );
    }

    /**
     * M8: a store that does not exist is refused in words, not with the database's error.
     *
     * @return void
     */
    public function testAStoreThatDoesNotExistIsRefusedInWords(): void
    {
        $identifier = 'mageos-faq-admin-' . uniqid();

        $this->post(['identifier' => $identifier, 'store_id' => '9999']);

        $this->assertSessionMessages(
            $this->equalTo(['The selected store no longer exists. Choose another and save again.']),
            MessageInterface::TYPE_ERROR
        );
        $this->assertNotContains(
            $identifier,
            $this->_objectManager->create(GroupReader::class)->getIdentifiers()
        );
    }

    /**
     * A successful save leaves no input from an earlier failed one behind.
     *
     * @return void
     */
    public function testASuccessfulSaveClearsTheInputOfAFailedOne(): void
    {
        $persistor = $this->_objectManager->get(DataPersistorInterface::class);
        $persistor->set('mageos_faq', ['question' => 'From a failed save']);

        $this->post(['identifier' => 'mageos-faq-admin-' . uniqid()]);

        $this->assertNull($persistor->get('mageos_faq'));
    }

    /**
     * The data core's FAQ form shows the edit page for a FAQ (null: a new one).
     *
     * Read from the form component the way the edit page reads it, from a request with only the
     * edit page's parameters: Magento\Ui\Component\Form::getDataSourceData() looks the record up
     * under its id, or '' for a new one. A second dispatch in one test can't render the page: the
     * test framework's Application::run() reuses the App\Http from the first, with its request.
     *
     * @param int|null $entityId
     * @return array<string, mixed>
     */
    private function formData(?int $entityId): array
    {
        $this->resetRequest();
        if ($entityId !== null) {
            $this->getRequest()->setParam('entity_id', (string) $entityId);
        }

        $form = $this->_objectManager->get(UiComponentFactory::class)->create('mageos_faq_form');
        $this->prepare($form);

        return $form->getDataSourceData()['data'] ?? [];
    }

    /**
     * Prepare a UI component and its children, as rendering does.
     *
     * @param UiComponentInterface $component
     * @return void
     */
    private function prepare(UiComponentInterface $component): void
    {
        foreach ($component->getChildComponents() as $child) {
            $this->prepare($child);
        }
        $component->prepare();
    }

    /**
     * Post the FAQ form: a valid new FAQ for all store views, with the given fields replaced.
     *
     * @param array<string, string> $fields
     * @return void
     */
    private function post(array $fields): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($fields + [
            'store_id'  => '0',
            'question'  => 'Where is my order?',
            'answer'    => 'On its way.',
            'is_active' => '1',
        ]);
        $this->dispatch($this->uri);
    }
}
