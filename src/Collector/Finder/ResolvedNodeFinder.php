<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use Efabrica\PHPStanLatte\Collector\Collector\ResolvedNodeCollector;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteCustomTemplateResolverInterface;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteFileTemplateResolverInterface;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use PHPStan\Node\CollectedDataNode;

/**
 * @phpstan-import-type CollectedResolvedNodeArray from CollectedResolvedNode
 */
final class ResolvedNodeFinder
{
    /**
     * @var array<string, CollectedResolvedNode[]>
     */
    private array $collectedResolvedNodes = [];

    /**
     * @var array<string>
     */
    private array $analysedFiles = [];

    /**
     * @param string[] $existingTemplates
     * @param LatteTemplateResolverInterface[] $latteTemplateResolvers
     */
    public function __construct(CollectedDataNode $collectedDataNode, array $existingTemplates, array $latteTemplateResolvers)
    {
        // node resolvers
        $collectedResolvedNodes = ResolvedNodeCollector::loadData($collectedDataNode, CollectedResolvedNode::class);

        // file resolvers
        foreach ($existingTemplates as $templateFile) {
            foreach ($latteTemplateResolvers as $latteTemplateResolver) {
                if ($latteTemplateResolver instanceof LatteFileTemplateResolverInterface) {
                    $collectedResolvedNodes = array_merge($collectedResolvedNodes, $latteTemplateResolver->collect($templateFile));
                }
            }
        }

        // custom resolvers
        foreach ($latteTemplateResolvers as $latteTemplateResolver) {
            if ($latteTemplateResolver instanceof LatteCustomTemplateResolverInterface) {
                $collectedResolvedNodes = array_merge($collectedResolvedNodes, $latteTemplateResolver->collect());
            }
        }

        foreach ($collectedResolvedNodes as $collectedResolvedNode) {
            $resolver = $collectedResolvedNode->getResolver();
            if (!isset($this->collectedResolvedNodes[$resolver])) {
                $this->collectedResolvedNodes[$resolver] = [];
            }
            $this->collectedResolvedNodes[$resolver][] = $collectedResolvedNode;
            $this->analysedFiles[] = $collectedResolvedNode->getAnalysedFile();
        }
    }

    /**
     * @return CollectedResolvedNode[]
     */
    public function find(string $resolver): array
    {
        return $this->collectedResolvedNodes[$resolver] ?? [];
    }

    /**
     * @return string[]
     */
    public function getAnalysedFiles(): array
    {
        return array_unique($this->analysedFiles);
    }
}
