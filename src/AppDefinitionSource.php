<?php

declare(strict_types=1);

namespace GSU\CoreIMPACTS;

use GSU\CoreIMPACTS\Model\AppConfig;
use GSU\D2L\API\D2LAPIDefinitionSource;
use mjfklib\Container\DefinitionSource;
use mjfklib\Container\Env;

class AppDefinitionSource extends DefinitionSource
{
    /**
     * @inheritdoc
     */
    protected function createDefinitions(Env $env): array
    {
        return [
            AppConfig::class => self::factory([AppConfig::class, 'create'], [
                'values' => $env
            ])
        ];
    }


    /**
     * @inheritdoc
     */
    public function getSources(): array
    {
        return [
            D2LAPIDefinitionSource::class
        ];
    }
}
