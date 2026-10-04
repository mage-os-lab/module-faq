<?php

declare(strict_types=1);

namespace MageOS\Faq\Model\ResourceModel\Faq;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MageOS\Faq\Model\Faq as FaqModel;
use MageOS\Faq\Model\ResourceModel\Faq as FaqResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Initialize the collection model and resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(FaqModel::class, FaqResource::class);
    }
}
