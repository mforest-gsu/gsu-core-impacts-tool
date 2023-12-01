<?php

declare(strict_types=1);

namespace GSU\CoreIMPACTS\LinkTemplatesToAreas\Actions;

use GSU\CoreIMPACTS\Model\AppConfig;

class GetTemplateAreas
{
    /**
     * @param AppConfig $config
     */
    public function __construct(private AppConfig $config)
    {
    }


    /**
     * @return array<string,string[]>
     */
    public function __invoke(): array
    {
        $templateAreas = [];

        foreach ($this->config->areas as $area) {
            $areaId = strval($area->id);
            foreach ($area->templates as $template) {
                if (!isset($templateAreas[$template])) {
                    $templateAreas[$template] = [];
                }
                $templateAreas[$template][$areaId] = $areaId;
            }
        }

        return $templateAreas;
    }
}
