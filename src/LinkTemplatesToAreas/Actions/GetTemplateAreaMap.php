<?php

declare(strict_types=1);

namespace GSU\CoreIMPACTS\LinkTemplatesToAreas\Actions;

class GetTemplateAreaMap
{
    /**
     * @param GetTemplateAreas $getTemplateAreas
     * @param GetCourseTemplates $getCourseTemplates
     */
    public function __construct(
        private GetTemplateAreas $getTemplateAreas,
        private GetCourseTemplates $getCourseTemplates
    ) {
    }


    /**
     * @return array<string,array<string,string>>
     */
    public function __invoke(): array
    {
        $templateAreas = ($this->getTemplateAreas)();
        $courseTemplates = ($this->getCourseTemplates)();

        $templateAreaMap = [];
        foreach ($courseTemplates as $templateCode => $templates) {
            foreach ($templates as $templateId) {
                if (isset($templateAreas[$templateCode])) {
                    $templateAreaMap[$templateId] = $templateAreas[$templateCode];
                }
            }
        }

        return $templateAreaMap;
    }
}
