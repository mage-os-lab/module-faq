<?php

declare(strict_types=1);

namespace MageOS\Faq\Test\Unit\Model\Faq;

use MageOS\Faq\Model\Faq;
use MageOS\Faq\Model\Faq\Identifier;
use PHPUnit\Framework\TestCase;

/**
 * Issues #1, #3 and #4: one rule for what a FAQ group identifier may be, and for the cache tag it
 * becomes.
 */
class IdentifierTest extends TestCase
{
    public function testOnlyLowercaseLettersDigitsHyphensAndUnderscoresAreValid(): void
    {
        $identifier = new Identifier();

        foreach (['shipping', 'returns-eu', 'faq_2', '0', '123', '-7', '01', str_repeat('a', 128)] as $valid) {
            $this->assertTrue($identifier->isValid($valid), $valid);
        }
        foreach (['', 'Shipping', 'shipping & returns', "shipping\r\nX-Injected: yes", 'faq[', 'returns(',
            '50%', 'a,b', 'café', str_repeat('a', 129)] as $invalid) {
            $this->assertFalse($identifier->isValid($invalid), $invalid);
        }
    }

    public function testAValidIdentifierNormalizesToItself(): void
    {
        $identifier = new Identifier();

        foreach (['shipping', 'returns-eu', 'faq_2', '-7', '01'] as $valid) {
            $this->assertSame($valid, $identifier->normalize($valid));
        }
    }

    public function testOtherValuesNormalizeAsTheUpgradeRewroteThem(): void
    {
        $identifier = new Identifier();

        // What old content and stored settings refer to keeps finding the rewritten group.
        $this->assertSame('shipping', $identifier->normalize(' Shipping '));
        $this->assertSame('shipping-returns', $identifier->normalize('Shipping & Returns'));
        $this->assertSame(
            'shipping-returns',
            $identifier->normalize('shipping &amp; returns'),
            'as Page Builder stored it'
        );
        $this->assertSame('returns', $identifier->normalize('returns('));
        $this->assertSame('returns-eu', $identifier->normalize('returns <EU>'));
        $this->assertSame('shipping-x-injected-yes', $identifier->normalize("shipping\r\nX-Injected: yes"));
        $this->assertSame('children-s', $identifier->normalize('Children&apos;s'), 'HTML5 entities too');
        $this->assertSame('', $identifier->normalize(''));
        $this->assertSame('', $identifier->normalize("  \t"), 'blank is no group');
        $this->assertSame('group-' . substr(sha1('!!!'), 0, 8), $identifier->normalize('!!!'));
        $this->assertSame($identifier->normalize('!!!'), $identifier->normalize('!!!'), 'the same each time');
        $this->assertSame(str_repeat('a', 128), $identifier->normalize('!' . str_repeat('a', 130)), 'junk first');
        $this->assertSame(str_repeat('a', 128), $identifier->normalize(str_repeat('A', 200)), 'cut to the column');
        // Cut at 128, "a-b-…-b-" would end in a hyphen; that is trimmed too.
        $this->assertSame(substr(str_repeat('a-b-', 32), 0, 127), $identifier->normalize(str_repeat('a b ', 50)));
    }

    public function testOnlyAValidIdentifierBecomesATag(): void
    {
        $identifier = new Identifier();

        $this->assertSame(Faq::CACHE_TAG . '_group_shipping', $identifier->tag('shipping'));
        $this->assertNull($identifier->tag("shipping\r\nX-Injected: yes"));
        $this->assertNull($identifier->tag('faq['));
        $this->assertNull($identifier->tag(''));
    }
}
