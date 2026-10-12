<?php

declare(strict_types=1);

namespace MageOS\Faq\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageOS\Faq\Api\FaqRepositoryInterface;
use MageOS\Faq\Model\Faq as FaqModel;
use MageOS\Faq\Model\Faq\Identifier;
use MageOS\Faq\Model\ResourceModel\Faq\CollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Rewrites the FAQ group identifiers that 1.0.1's rules refuse into ones they accept.
 *
 * From 1.0.1 an identifier holds lowercase letters, digits, - and _ only (Model\Faq\Identifier),
 * and a FAQ with any other can no longer be saved — not even to change its answer. Each such
 * identifier becomes what Identifier::normalize() makes of it: "Shipping & Returns" becomes
 * "shipping-returns". Widgets, Page Builder elements and configuration naming the old identifier
 * keep finding the group, because their lookups normalise the same way.
 *
 * Two groups that normalise alike become one: "Shipping" and "shipping" already were one, since
 * the database compares identifiers without regard to case. Each rewrite is logged with the FAQ's
 * id, the old identifier and the new, noting when it joins a group that already had FAQs.
 *
 * Each FAQ is loaded and saved through the repository, so the save purges the cached pages showing
 * it, and whatever listens for FAQ saves (the llms documents, for one) hears about it.
 *
 * Not revertible: the log holds the old identifiers, and the rules no longer accept them.
 */
class NormalizeFaqIdentifiers implements DataPatchInterface
{
    /**
     * @param CollectionFactory $collectionFactory
     * @param FaqRepositoryInterface $faqRepository
     * @param LoggerInterface $logger
     * @param Identifier $identifier
     */
    public function __construct(
        private readonly CollectionFactory      $collectionFactory,
        private readonly FaqRepositoryInterface $faqRepository,
        private readonly LoggerInterface        $logger,
        private readonly Identifier             $identifier
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $collection = $this->collectionFactory->create();
        // Named first: a field added on its own replaces the whole select list, ID included.
        $collection->addFieldToSelect('entity_id');
        $collection->addFieldToSelect('identifier');

        $invalid = [];
        $inUse   = [];
        /** @var FaqModel $faq */
        foreach ($collection as $faq) {
            if ($this->identifier->isValid($faq->getIdentifier())) {
                $inUse[$faq->getIdentifier()] = true;
            } else {
                $invalid[$faq->getEntityId()] = $faq->getIdentifier();
            }
        }

        foreach ($invalid as $entityId => $old) {
            $new = $this->identifier->normalize($old);
            $faq = $this->faqRepository->getById($entityId);
            $faq->setIdentifier($new);
            $this->faqRepository->save($faq);

            $this->logger->notice(\sprintf(
                'MageOS_Faq: FAQ %d\'s group identifier %s is now "%s"%s.',
                $entityId,
                (string) json_encode($old, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $new,
                isset($inUse[$new]) ? ', joining the FAQs already in that group' : ''
            ));
            $inUse[$new] = true;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
