<?php

declare(strict_types=1);

namespace GSU\CoreIMPACTS\ImportOutcomesIntoCourses\Actions;

use GSU\CoreIMPACTS\Model\AppConfig;
use GSU\D2L\API\OrgUnits\OrgUnitsAPI;

class GetCourseOfferings
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
     * @param string|null $semesterCode
     * @param int|null $startOrgUnitId
     * @return array<string,string[]>
     */
    public function __invoke(
        ?string $semesterCode = null,
        ?int $startOrgUnitId = null
    ): array {
        $courseOfferings = [];

        foreach ($this->config->areas as $area) {
            $courses = [];
            $bookmark = $startOrgUnitId !== null ? $area->id . '_' . $startOrgUnitId : null;

            do {
                $descendants = $this->orgUnitsAPI->getDescendants(
                    $area->id,
                    $this->config->courseOfferingTypeId,
                    $bookmark
                );

                foreach ($descendants->Items as $orgUnit) {
                    $courseCode = explode('.', $orgUnit->Code ?? '');
                    if (count($courseCode) < 3) {
                        continue;
                    }
                    if (!($courseCode[0] === 'CO' && $courseCode[1] === $this->config->usgOrgId)) {
                        continue;
                    }
                    if ($semesterCode === null || end($courseCode) === $semesterCode) {
                        $courses[$orgUnit->Identifier] = $orgUnit->Identifier;
                    }
                }

                $bookmark = $descendants->PagingInfo->HasMoreItems ? $descendants->PagingInfo->Bookmark : null;
            } while ($bookmark !== null);

            $courseOfferings[$area->name] = $courses;
        }

        return $courseOfferings;
    }
}
