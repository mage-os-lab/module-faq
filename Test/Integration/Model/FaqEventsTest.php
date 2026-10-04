<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Integration\Model;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Faq\Api\Data\FaqInterface;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Model\Faq;
use PHPUnit\Framework\TestCase;

/**
 * Saving or deleting a FAQ, however it is done, dispatches `mageos_faq_save_after` /
 * `mageos_faq_delete_after`. Those names are the contract with whatever shows FAQs elsewhere:
 * MageOS_Seo's llms documents queue a rebuild on them, and test that side by dispatching them.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class FaqEventsTest extends TestCase
{
    /**
     * @return void
     */
    public function testSavingAndDeletingThroughTheRepositoryDispatchTheFaqEvents(): void
    {
        $events = $this->recordingEventManager();
        $faq    = $this->newFaq($events);
        $faqs   = Bootstrap::getObjectManager()->get(FaqRepositoryInterface::class);

        $faqs->save($faq);
        $this->assertContains('mageos_faq_save_after', $events->names);

        $faqs->delete($faq);
        $this->assertContains('mageos_faq_delete_after', $events->names);
    }

    /**
     * An active FAQ for every store view, not yet saved, whose events go through $events.
     *
     * @param ManagerInterface $events
     * @return FaqInterface
     */
    private function newFaq(ManagerInterface $events): FaqInterface
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var Faq $faq */
        $faq = $objectManager->create(Faq::class, [
            'context' => $objectManager->create(Context::class, ['eventDispatcher' => $events]),
        ]);
        $faq->setIdentifier('global')
            ->setStoreId(0)
            ->setQuestion('Do you ship worldwide?')
            ->setAnswer('An answer.')
            ->setSortOrder(0)
            ->setIsActive(true);

        return $faq;
    }

    /**
     * The real event manager, recording the name of every event dispatched through it.
     *
     * @return ManagerInterface&object{names: string[]}
     */
    private function recordingEventManager(): ManagerInterface
    {
        return new class (Bootstrap::getObjectManager()->get(ManagerInterface::class)) implements ManagerInterface {
            /**
             * @var string[]
             */
            public array $names = [];

            /**
             * @param ManagerInterface $inner
             */
            public function __construct(private readonly ManagerInterface $inner)
            {
            }

            /**
             * @inheritDoc
             */
            public function dispatch($eventName, array $data = [])
            {
                $this->names[] = $eventName;

                return $this->inner->dispatch($eventName, $data);
            }
        };
    }
}
