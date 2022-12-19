<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedMethodCall;
use Efabrica\PHPStanLatte\Resolver\CallResolver\CalledClassResolver;
use Efabrica\PHPStanLatte\Resolver\CallResolver\OutputCallResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends AbstractLatteContextCollector<Node, CollectedMethodCall>
 */
final class MethodOutputCollector extends AbstractLatteContextCollector
{
    private CalledClassResolver $calledClassResolver;

    private OutputCallResolver $outputCallResolver;

    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        CalledClassResolver $calledClassResolver,
        OutputCallResolver $outputCallResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->calledClassResolver = $calledClassResolver;
        $this->outputCallResolver = $outputCallResolver;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @phpstan-return null|CollectedMethodCall[]
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $functionName = $scope->getFunctionName();
        if ($functionName === null) {
            return null;
        }

        if (!$this->outputCallResolver->isOutputCallNode($node, $scope)) {
            return null;
        }

        $actualClassName = $classReflection->getName();
        $calledClassName = $this->calledClassResolver->resolve($node, $scope);
        $calledMethodName = $this->nameResolver->resolve($node);
        return [new CollectedMethodCall(
            $actualClassName,
            $functionName,
            $calledClassName ?? '',
            $calledMethodName ?? '',
            CollectedMethodCall::TERMINATING_CALL
        )];
    }
}
