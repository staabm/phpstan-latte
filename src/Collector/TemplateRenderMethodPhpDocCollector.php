<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedTemplateRender;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Template\Variable;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @phpstan-import-type CollectedTemplateRenderArray from CollectedTemplateRender
 * @extends AbstractCollector<ClassMethod, CollectedTemplateRender, CollectedTemplateRenderArray>
 */
final class TemplateRenderMethodPhpDocCollector extends AbstractCollector
{
    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        TypeSerializer $typeSerializer,
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        Parser $parser,
        NodeScopeResolver $nodeScopeResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($typeSerializer, $nameResolver, $reflectionProvider, $parser, $nodeScopeResolver);
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     * @phpstan-return null|CollectedTemplateRenderArray[]
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $methodName = $this->nameResolver->resolve($node);
        if ($methodName === null) {
            return null;
        }

        $lattePhpDoc = $this->lattePhpDocResolver->resolveForMethod($classReflection->getName(), $methodName);
        if ($lattePhpDoc->isIgnored()) {
            return null;
        }

        $variables = [];
        foreach ($lattePhpDoc->getVariablesWithParents() as $name => $type) {
            if ($name === '') {
                continue; // method annotation without variable name not allowed
            }
            $variables[$name] = new Variable($name, $type);
        }

        $templateRenders = [];
        foreach ($lattePhpDoc->getTemplatePathsWithParents() as $templatePath) {
            $templateRenders[] = CollectedTemplateRender::build($node, $scope, $templatePath, $variables);
        }

        return $this->collectItems($templateRenders);
    }
}
