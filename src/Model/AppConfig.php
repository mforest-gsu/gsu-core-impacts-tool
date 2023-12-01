<?php

declare(strict_types=1);

namespace GSU\CoreIMPACTS\Model;

use GSU\D2L\API\Outcomes\Model\OutcomeDetails;
use mjfklib\Container\ArrayValue;
use mjfklib\Container\Env;
use mjfklib\Container\ObjectFactory;

class AppConfig
{
    /**
     * @param mixed $values
     * @return self
     */
    public static function create(mixed $values): self
    {
        if ($values instanceof Env) {
            $confDir = $values['CONF_DIR'] ?? "{$values->appDir}/conf";
            $workDir = $values['WORK_DIR'] ?? "{$values->appDir}/work";
            $values = [
                'cacheFile' => $values['CORE_IMPACTS_CACHE_FILE'] ?? "{$workDir}/lastOrgUnit.txt",
                'usgOrgId' => $values['CORE_IMPACTS_USG_ORG'] ?? null,
                'semesterCode' => $values['CORE_IMPACTS_SEMESTER_CODE'] ?? null,
                'rootOrgUnitId' => $values['D2L_ROOT_ORG_UNIT'] ?? null,
                'templateTypeId' => $values['D2L_TEMPLATE_TYPE'] ?? 2,
                'courseOfferingTypeId' => $values['D2L_OFFERING_TYPE'] ?? 3,
                'areasFile' => $values['CORE_IMPACTS_AREAS_FILE'] ?? "{$confDir}/areas.json",
                'outcomesFile' => $values['CORE_IMPACTS_OUTCOMES_FILE'] ?? "{$confDir}/outcomes.json"
            ];
        }

        return ObjectFactory::createObject(
            $values,
            self::class,
            fn (array $values): self => self::construct($values)
        );
    }


    /**
     * @param mixed[] $values
     * @return self
     */
    private static function construct(array $values): self
    {
        /** @var array<string,OutcomeDetails> $outcomes */
        $outcomes = array_column(
            array_map(
                fn ($v) => OutcomeDetails::create($v),
                self::getArrayValues($values, 'outcomesFile', 'outcomes')
            ),
            null,
            'description'
        );

        // The root outcome will always be the first one in the list
        $rootOutcome = count($outcomes) > 0 ? reset($outcomes) : null;
        if ($rootOutcome === null) {
            throw new \RuntimeException('Expected at least one outcome');
        }

        return new self(
            cacheFile: ArrayValue::getString($values, 'cacheFile'),
            usgOrgId: ArrayValue::getString($values, 'usgOrgId'),
            semesterCode: ArrayValue::getString($values, 'semesterCode'),
            rootOrgUnitId: ArrayValue::getInt($values, 'rootOrgUnitId'),
            templateTypeId: ArrayValue::getInt($values, 'templateTypeId'),
            courseOfferingTypeId: ArrayValue::getInt($values, 'courseOfferingTypeId'),
            outcomes: $outcomes,
            areas: array_column(
                array_map(
                    fn (mixed $v) => CoreArea::create($v, $outcomes, $rootOutcome),
                    self::getArrayValues($values, 'areasFile', 'areas')
                ),
                null,
                'name'
            )
        );
    }


    /**
     * @param mixed[] $values
     * @param string $valuesFileName
     * @param string $valuesName
     * @return mixed[]
     */
    private static function getArrayValues(
        array $values,
        string $valuesFileName,
        string $valuesName
    ): array {
        $valuesFile = ArrayValue::getStringNull($values, $valuesFileName);

        if (is_string($valuesFile)) {
            $valuesString = @file_get_contents($valuesFile);
            if (!is_string($valuesString)) {
                throw new \RuntimeException("Unable to get contents of file: {$valuesFile}");
            }
            $arrayValues = json_decode($valuesString, true, 16, JSON_PRETTY_PRINT);
            if (!is_array($arrayValues)) {
                throw new \RuntimeException("Unable to read contents of file: {$valuesFile}");
            }
        } else {
            $arrayValues = ArrayValue::getArray($values, $valuesName);
        }

        return array_values($arrayValues);
    }


    /**
     * @param string $cacheFile
     * @param string $usgOrgId
     * @param string $semesterCode
     * @param int $rootOrgUnitId
     * @param int $templateTypeId
     * @param int $courseOfferingTypeId
     * @param array<string,OutcomeDetails> $outcomes
     * @param array<string,CoreArea> $areas
     */
    public function __construct(
        public string $cacheFile,
        public string $usgOrgId,
        public string $semesterCode,
        public int $rootOrgUnitId,
        public int $templateTypeId,
        public int $courseOfferingTypeId,
        public array $outcomes,
        public array $areas
    ) {
    }
}
