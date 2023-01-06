<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\LatteCustomTemplateResolver;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\CollectorResultTest;

final class CollectorResultForCustomTemplateResolverTest extends CollectorResultTest
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/../../../config.neon',
            __DIR__ . '/config.neon',
        ];
    }

    public function testResolver(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/SomeControl.php'], [
            'NODE NetteApplicationUIControl {"className":"SomeControl"}',
            'NODE TestingCustomTemplateResolver {"template":"/LatteCustomTemplateResolver/Fixtures/templates/default.latte"}',
            'TEMPLATE default.latte Control::resolved ["someVariable"] []',
            'NODE TestingCustomTemplateResolver {"template":"/LatteCustomTemplateResolver/Fixtures/templates/other.latte"}',
            'TEMPLATE other.latte Control::resolved ["someVariable"] []',
        ]);
    }
}
