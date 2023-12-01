<?php

declare(strict_types=1);

namespace GSU\CoreIMPACTS\ImportOutcomesIntoCourses\Actions;

use GSU\CoreIMPACTS\Model\AppConfig;
use GSU\D2L\API\Outcomes\Model\OutcomeRegistry;

class GetAreaRegistry
{
    /**
     * @param AppConfig $config
     */
    public function __construct(private AppConfig $config)
    {
    }


    /**
     * @param string $areaName
     * @return OutcomeRegistry
     */
    public function __invoke(string $areaName): OutcomeRegistry
    {
        $area = $this->config->areas[$areaName] ?? throw new \RuntimeException();
        return new OutcomeRegistry(
            $area->name,
            array_values($area->outcomes)
        );
    }
}
