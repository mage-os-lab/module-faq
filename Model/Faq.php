<?php

declare(strict_types=1);

namespace MageOS\Faq\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use MageOS\Faq\Api\Data\FaqExtensionInterface;
use MageOS\Faq\Api\Data\FaqInterface;
use MageOS\Faq\Model\Faq\Identifier;
use MageOS\Faq\Model\ResourceModel\Faq as FaqResource;

class Faq extends AbstractExtensibleModel implements FaqInterface, IdentityInterface
{
    /**
     * Cache tag prefix; FAQ-rendering blocks emit matching identities so FPC pages
     * are purged automatically when a FAQ is saved or deleted (AbstractModel
     * dispatches clean_cache_by_tags with this model's identities).
     */
    public const CACHE_TAG = 'mageos_faq';

    /**
     * Saves and deletes dispatch `mageos_faq_save_after` / `_delete_after`, so whatever shows
     * FAQs elsewhere (the llms documents, for one) can react to any save — admin, API, import.
     *
     * @var string
     */
    protected $_eventPrefix = 'mageos_faq';

    /**
     * @var string
     */
    protected $_eventObject = 'faq';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Identifier $identifier
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param mixed[] $data
     */
    public function __construct(
        Context                     $context,
        Registry                    $registry,
        ExtensionAttributesFactory  $extensionFactory,
        AttributeValueFactory       $customAttributeFactory,
        private readonly Identifier $identifier,
        ?AbstractResource           $resource = null,
        ?AbstractDb                 $resourceCollection = null,
        array                       $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(FaqResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getIdentities(): array
    {
        $identities = [self::CACHE_TAG];
        $tag        = $this->identifier->tag($this->getIdentifier());
        if ($tag !== null) {
            $identities[] = $tag;
        }
        // When the group identifier changes, pages caching the old group need purging too.
        $origIdentifier = (string) $this->getOrigData(self::IDENTIFIER);
        $origTag        = $this->identifier->tag($origIdentifier);
        if ($origTag !== null && $origIdentifier !== $this->getIdentifier()) {
            $identities[] = $origTag;
        }

        return $identities;
    }

    /**
     * Refuse a group identifier outside lowercase letters, digits, - and _ (Faq\Identifier).
     *
     * Here rather than in the admin controller, so it holds however a FAQ is saved: the admin, the
     * repository, a data patch or an import.
     *
     * @throws LocalizedException
     * @return $this
     */
    public function beforeSave()
    {
        if (!$this->identifier->isValid($this->getIdentifier())) {
            throw new LocalizedException(__(
                'The FAQ group identifier can only contain lowercase letters, digits, - and _, up to 128'
                . ' characters.'
            ));
        }

        return parent::beforeSave();
    }

    /**
     * @inheritdoc
     */
    public function getEntityId(): int
    {
        return (int) $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return (string) $this->getData(self::IDENTIFIER);
    }

    /**
     * @inheritdoc
     */
    public function setIdentifier(string $identifier): FaqInterface
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): int
    {
        return (int) $this->getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId(int $storeId): FaqInterface
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritdoc
     */
    public function getQuestion(): string
    {
        return (string) $this->getData(self::QUESTION);
    }

    /**
     * @inheritdoc
     */
    public function setQuestion(string $question): FaqInterface
    {
        return $this->setData(self::QUESTION, $question);
    }

    /**
     * @inheritdoc
     */
    public function getAnswer(): string
    {
        return (string) $this->getData(self::ANSWER);
    }

    /**
     * @inheritdoc
     */
    public function setAnswer(string $answer): FaqInterface
    {
        return $this->setData(self::ANSWER, $answer);
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder(): int
    {
        return (int) $this->getData(self::SORT_ORDER);
    }

    /**
     * @inheritdoc
     */
    public function setSortOrder(int $sortOrder): FaqInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * @inheritdoc
     */
    public function getIsActive(): bool
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * @inheritdoc
     */
    public function setIsActive(bool $isActive): FaqInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive ? 1 : 0);
    }

    /**
     * @inheritdoc
     *
     * AbstractExtensibleModel creates the object on first read, so a caller never meets null.
     */
    public function getExtensionAttributes(): ?FaqExtensionInterface
    {
        /** @var FaqExtensionInterface|null $extensionAttributes */
        $extensionAttributes = $this->_getExtensionAttributes();

        return $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(FaqExtensionInterface $extensionAttributes): FaqInterface
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
