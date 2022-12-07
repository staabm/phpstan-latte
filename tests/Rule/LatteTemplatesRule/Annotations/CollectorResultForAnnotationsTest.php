<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations;

use Nette\Utils\Finder;

final class CollectorResultForAnnotationsTest extends ScanCollectorResultTest
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/../../../config.neon',
        ];
    }

    /**
     * @dataProvider fixtures
     */
    public function testFixture(string $fixtureName): void
    {
        $this->resolveFixture(
            __DIR__ . '/Fixtures/' . $fixtureName,
            __NAMESPACE__ . '\\Fixtures\\' . $fixtureName,
        );
    }

    public function fixtures(): array
    {
        $fixtures = [];
        foreach (Finder::findDirectories()->in(__DIR__ . '/Fixtures') as $path) {
            $fixtures[] = [$path->getFilename()];
        }
        return $fixtures;
    }
}
