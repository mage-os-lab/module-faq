<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Unit\Model;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use MageOS\Faq\Api\Data\FaqInterface;
use MageOS\Faq\Api\Data\FaqSearchResultsInterfaceFactory;
use MageOS\Faq\Model\Faq;
use MageOS\Faq\Model\FaqFactory;
use MageOS\Faq\Model\FaqRepository;
use MageOS\Faq\Model\ResourceModel\Faq as FaqResource;
use MageOS\Faq\Model\ResourceModel\Faq\CollectionFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * What a save or delete reports when it cannot be done: a model the resource model cannot handle,
 * and a database that refuses the write. The round trips themselves are covered against a real
 * database by Test/Integration/Model/FaqRepositoryTest, which cannot make the database fail.
 *
 * The model, collection and search-results factories are Magento's generated classes, so this test
 * needs an installation to have generated them. The mutation-testing run works from the module
 * directory alone and excludes this group; the unit job, which runs inside an installation, does
 * not.
 *
 * @group magento-generated
 */
#[Group('magento-generated')]
class FaqRepositoryTest extends TestCase
{
    /**
     * What the resource model's save() and delete() throw, or null when they succeed.
     *
     * @var \Throwable|null
     */
    private ?\Throwable $resourceFailure = null;

    protected function setUp(): void
    {
        $this->resourceFailure = null;
    }

    public function testAModelTheResourceCannotSaveIsRefusedAsCouldNotSave(): void
    {
        $this->expectException(CouldNotSaveException::class);

        $this->repository()->save($this->createStub(FaqInterface::class));
    }

    public function testASaveTheDatabaseRefusesIsCouldNotSaveWithTheCauseKept(): void
    {
        $this->resourceFailure = new \RuntimeException('Deadlock found');

        try {
            $this->repository()->save($this->createStub(Faq::class));
            $this->fail('The failed save was not reported.');
        } catch (CouldNotSaveException $e) {
            $this->assertSame($this->resourceFailure, $e->getPrevious());
        }
    }

    public function testAModelTheResourceCannotDeleteIsRefusedAsCouldNotDelete(): void
    {
        $this->expectException(CouldNotDeleteException::class);

        $this->repository()->delete($this->createStub(FaqInterface::class));
    }

    public function testADeleteTheDatabaseRefusesIsCouldNotDeleteWithTheCauseKept(): void
    {
        $this->resourceFailure = new \RuntimeException('Lock wait timeout');

        try {
            $this->repository()->delete($this->createStub(Faq::class));
            $this->fail('The failed delete was not reported.');
        } catch (CouldNotDeleteException $e) {
            $this->assertSame($this->resourceFailure, $e->getPrevious());
        }
    }

    /**
     * The repository over a resource model whose save() and delete() throw $resourceFailure when
     * it is set.
     *
     * @return FaqRepository
     */
    private function repository(): FaqRepository
    {
        $resource = $this->createStub(FaqResource::class);
        $resource->method('save')->willReturnCallback(
            function () use ($resource): FaqResource {
                if ($this->resourceFailure !== null) {
                    throw $this->resourceFailure;
                }
                return $resource;
            }
        );
        $resource->method('delete')->willReturnCallback(
            function () use ($resource): FaqResource {
                if ($this->resourceFailure !== null) {
                    throw $this->resourceFailure;
                }
                return $resource;
            }
        );

        return new FaqRepository(
            $this->createStub(FaqFactory::class),
            $resource,
            $this->createStub(CollectionFactory::class),
            $this->createStub(JoinProcessorInterface::class),
            $this->createStub(CollectionProcessorInterface::class),
            $this->createStub(FaqSearchResultsInterfaceFactory::class)
        );
    }
}
