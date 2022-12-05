<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

use Latte\Engine;
use Latte\Runtime\Defaults;
use Latte\Runtime\FilterExecutor;
use Nette\Bridges\ApplicationLatte\UIMacros;
use Nette\Bridges\FormsLatte\FormMacros;
use ReflectionClass;
use ReflectionException;

final class Latte2Compiler extends AbstractCompiler
{
    /**
     * @param array<string, string|array{string, string}> $filters
     * @param string[] $macros
     */
    public function __construct(
        ?Engine $engine = null,
        bool $strictMode = false,
        array $filters = [],
        array $macros = []
    ) {
        parent::__construct($engine, $strictMode, $filters);
        $this->installMacros($macros);
    }

    protected function createDefaultEngine(): Engine
    {
        $engine = new Engine();
        if (class_exists(UIMacros::class)) {
            UIMacros::install($engine->getCompiler());
        }
        if (class_exists(FormMacros::class)) {
            FormMacros::install($engine->getCompiler());
        }
        return $engine;
    }

    public function compile(string $templateContent, ?string $actualClass, string $context = ''): string
    {
        $latteTokens = $this->engine->getParser()->parse($templateContent);
        $className = $this->generateClassName();
        $phpContent = $this->engine->getCompiler()->compile(
            $latteTokens,
            $className,
            $this->generateClassComment($className, $context),
            $this->strictMode
        );
        $phpContent = $this->fixLines($phpContent);
        $phpContent = $this->addTypes($phpContent, $className, $actualClass);
        return $phpContent;
    }

    public function getFilters(): array
    {
        try {
            $engineFilters = $this->getEngineFiltersByReflection();
        } catch (ReflectionException $e) {
            $engineFilters = $this->getDefaultFilters();
        }
        return array_merge($engineFilters, $this->filters);
    }

    /**
     * @return array<string, string|array{string, string}|callable>
     */
    private function getEngineFiltersByReflection(): array
    {
        // we must use try to use reflection to get to filter signatures in Latte 2 :-(
        $engineFiltersPropertyReflection = (new ReflectionClass($this->engine))->getProperty('filters');
        $engineFiltersPropertyReflection->setAccessible(true);
        /** @var FilterExecutor $engineFiltersProperty */
        $engineFiltersProperty = $engineFiltersPropertyReflection->getValue($this->engine);

        $engineFiltersStaticPropertyReflection = (new ReflectionClass($engineFiltersProperty))->getProperty('_static');
        $engineFiltersStaticPropertyReflection->setAccessible(true);
        /** @var array<string, array{callable, ?bool}> */
        $engineFiltersStaticProperty = $engineFiltersStaticPropertyReflection->getValue($engineFiltersProperty);

        $engineFilters = [];
        foreach ($engineFiltersStaticProperty as $filterName => $filterData) {
            $engineFilters[strtolower($filterName)] = $filterData[0];
        }
        return $engineFilters;
    }

    /**
     * @return array<string, string|array{string, string}|callable>
     */
    private function getDefaultFilters()
    {
        return array_change_key_case((new Defaults())->getFilters());
    }

    /**
     * @param string[] $macros
     */
    private function installMacros(array $macros): void
    {
        foreach ($macros as $macro) {
            [$class, $method] = explode('::', $macro, 2);
            $callable = [$class, $method];
            if (is_callable($callable)) {
                call_user_func($callable, $this->engine->getCompiler());
            }
        }
    }

    private function fixLines(string $phpContent): string
    {
        // fix lines at the end of lines
        $pattern = '/(.*?) (?<line>\/\*(.*?)line (?<number>\d+)(.*?)\*\/)/';
        return preg_replace($pattern, '${2}${1}', $phpContent) ?: '';
    }
}
