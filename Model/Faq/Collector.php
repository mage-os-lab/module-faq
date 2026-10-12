<?php

declare(strict_types=1);

namespace MageOS\Faq\Model\Faq;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use MageOS\Faq\Api\FaqCollectorInterface;

/**
 * Request-scoped FAQ identifier collector (one shared instance per request, like SchemaRegistry).
 */
class Collector implements FaqCollectorInterface, ResetAfterRequestInterface
{
    /**
     * @var array<string, true>
     */
    private array $identifiers = [];

    /**
     * @inheritdoc
     */
    public function collect(string $identifier): void
    {
        if ($identifier !== '') {
            $this->identifiers[$identifier] = true;
        }
    }

    /**
     * @inheritdoc
     */
    public function getIdentifiers(): array
    {
        // PHP turns a numeric-string array key into an int; give "123" back as the string it was.
        return array_map('strval', array_keys($this->identifiers));
    }

    /**
     * Clear collected identifiers between worker-mode requests.
     *
     * @return void
     */
    public function _resetState(): void // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- framework interface
    {
        $this->identifiers = [];
    }
}
