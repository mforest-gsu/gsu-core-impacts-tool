<?php

declare(strict_types=1);

namespace GSU\CoreIMPACTS\LinkTemplatesToAreas\Actions;

use GSU\CoreIMPACTS\Model\AppConfig;
use GSU\D2L\API\OrgUnits\Model\OrgUnit;
use GSU\D2L\API\OrgUnits\OrgUnitsAPI;

class GetCourseTemplates
{
    /**
     * @param AppConfig $config
     * @param OrgUnitsAPI $orgUnitsAPI
     */
    public function __construct(
        private AppConfig $config,
        private OrgUnitsAPI $orgUnitsAPI
    ) {
    }


    /**
     * @return array<string,array<string,string>>
     */
    public function __invoke(): array
    {
        $templates = [];
        $bookmark = null;

        do {
            $descendants = $this->orgUnitsAPI->getDescendants(
                $this->config->rootOrgUnitId,
                $this->config->templateTypeId,
                $bookmark
            );

            foreach ($descendants->Items as $orgUnit) {
                $courseCode = $this->getCourseCode($orgUnit, $this->config->usgOrgId);
                if ($courseCode !== null) {
                    if (!isset($templates[$courseCode])) {
                        $templates[$courseCode] = [];
                    }

                    $templates[$courseCode][$orgUnit->Identifier] = $orgUnit->Identifier;
                }
            }

            $bookmark = $descendants->PagingInfo->HasMoreItems ? $descendants->PagingInfo->Bookmark : null;
        } while ($bookmark !== null);

        return $templates;
    }


    /**
     * @param OrgUnit $orgUnit
     * @param string $usgOrgId
     * @return string|null
     */
    private function getCourseCode(
        OrgUnit $orgUnit,
        string $usgOrgId
    ): ?string {
        $code = explode('.', $orgUnit->Code ?? '');
        return (count($code) > 1 && $code[0] === $usgOrgId) ? end($code) : null;
    }
}
