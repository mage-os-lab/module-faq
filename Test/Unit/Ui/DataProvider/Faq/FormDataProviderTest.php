<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Unit\Ui\DataProvider\Faq;

use Magento\Framework\App\Request\DataPersistorInterface;
use MageOS\Faq\Model\Faq;
use MageOS\Faq\Model\ResourceModel\Faq\Collection;
use MageOS\Faq\Model\ResourceModel\Faq\CollectionFactory;
use MageOS\Faq\Ui\DataProvider\Faq\FormDataProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Issue #7: what was submitted in a failed save comes back in the form.
 *
 * Core's form looks a record up under its id from the request, and a new record under '' (no id in
 * the request): Magento\Ui\Component\Form::getDataSourceData() reads $data[$id ?? ''].
 *
 * The collection factory is one of Magento's generated classes, so this test needs an
 * installation to have generated it. The mutation-testing run works from the module directory
 * alone and excludes this group; the unit job, which runs inside an installation, does not.
 *
 * @group magento-generated
 */
#[Group('magento-generated')]
class FormDataProviderTest extends TestCase
{
    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function newRecordInputProvider(): array
    {
        $input = ['identifier' => 'shipping', 'question' => 'Kept?'];

        return [
            'no entity_id'      => [$input],
            'empty entity_id'   => [$input + ['entity_id' => '']],
            'entity_id "0"'     => [$input + ['entity_id' => '0']],
            'entity_id 0'       => [$input + ['entity_id' => 0]],
        ];
    }

    /**
     * @dataProvider newRecordInputProvider
     * @param array<string, mixed> $input
     * @return void
     */
    #[DataProvider('newRecordInputProvider')]
    public function testANewRecordsInputComesBackUnderTheNewFormKey(array $input): void
    {
        $data = $this->provider([], $input)->getData();

        $this->assertSame(['' => $input], $data);
    }

    public function testAnExistingRecordsInputReplacesItsStoredRow(): void
    {
        $input = ['entity_id' => '7', 'identifier' => 'returns', 'question' => 'Kept?'];

        $data = $this->provider([7 => ['entity_id' => '7', 'question' => 'Stored']], $input)->getData();

        $this->assertSame([7 => $input], $data);
    }

    public function testWithoutAFailedSaveTheStoredRowsAreGiven(): void
    {
        $data = $this->provider([7 => ['entity_id' => '7', 'question' => 'Stored']], null)->getData();

        $this->assertSame([7 => ['entity_id' => '7', 'question' => 'Stored']], $data);
    }

    public function testTheInputIsReadOnce(): void
    {
        $persistor = $this->createMock(DataPersistorInterface::class);
        $persistor->method('get')->willReturn(['question' => 'Kept?']);
        $persistor->expects($this->once())->method('clear')->with('mageos_faq');

        $provider = $this->provider([], null, $persistor);
        $provider->getData();
        $provider->getData();
    }

    /**
     * A provider over stored rows (id => data) and what the persistor holds from a failed save.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, mixed>|null $persisted
     * @param DataPersistorInterface|null $persistor
     * @return FormDataProvider
     */
    private function provider(
        array $rows,
        ?array $persisted,
        ?DataPersistorInterface $persistor = null
    ): FormDataProvider {
        $items = [];
        foreach ($rows as $id => $row) {
            $faq = $this->createStub(Faq::class);
            $faq->method('getId')->willReturn($id);
            $faq->method('getData')->willReturn($row);
            $items[$id] = $faq;
        }

        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')->willReturn($items);
        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        if ($persistor === null) {
            $persistor = $this->createStub(DataPersistorInterface::class);
            $persistor->method('get')->willReturn($persisted);
        }

        return new FormDataProvider(
            'mageos_faq_form_data_source',
            'entity_id',
            'entity_id',
            $collectionFactory,
            $persistor
        );
    }
}
