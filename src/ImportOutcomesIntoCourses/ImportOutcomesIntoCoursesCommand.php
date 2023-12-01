<?php

declare(strict_types=1);

namespace GSU\CoreIMPACTS\ImportOutcomesIntoCourses;

use GSU\CoreIMPACTS\ImportOutcomesIntoCourses\Actions\GetAreaRegistry;
use GSU\CoreIMPACTS\ImportOutcomesIntoCourses\Actions\GetCourseOfferings;
use GSU\D2L\API\Outcomes\OutcomesAPI;
use GSU\CoreIMPACTS\Model\AppConfig;
use mjfklib\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'courses:import-outcomes')]
class ImportOutcomesIntoCoursesCommand extends Command
{
    /**
     * @param AppConfig $config
     * @param GetAreaRegistry $getAreaRegistry
     * @param GetCourseOfferings $getCourseOfferings
     * @param OutcomesAPI $outcomesAPI
     */
    public function __construct(
        private AppConfig $config,
        private GetAreaRegistry $getAreaRegistry,
        private GetCourseOfferings $getCourseOfferings,
        private OutcomesAPI $outcomesAPI
    ) {
        parent::__construct(
            logStartFinish: true,
            logError: true,
        );
    }


    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption(
            name: 'cache-file',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'File to store the last OrgUnitId',
            default: $this->config->cacheFile
        );

        $this->addOption(
            name: 'semester-code',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Semester to run this process for',
            default: $this->config->semesterCode
        );

        $this->addOption(
            name: 'start-org-unit',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Starting Org Unit Id',
            default: null
        );
    }


    /**
     * @param InputInterface $input
     * @return array{0:string,1:string,2:int|null}
     */
    protected function collectInputs(InputInterface $input): array
    {
        $cacheFile = $input->getOption('cache-file');
        if (!is_string($cacheFile)) {
            $cacheFile = $this->config->cacheFile;
        }

        $semesterCode = $input->getOption('semester-code');
        if (!is_string($semesterCode)) {
            $semesterCode = $this->config->semesterCode;
        }

        $startOrgUnitId = $input->getOption('start-org-unit');
        $startOrgUnitId = is_scalar($startOrgUnitId) ? intval($startOrgUnitId) : null;

        return [
            $this->config->cacheFile,
            $this->config->semesterCode,
            $startOrgUnitId
        ];
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        list(
            $cacheFile,
            $semesterCode,
            $startOrgUnitId
        ) = $this->collectInputs($input);

        $imported = $total = 0;

        // Get last OrgUnitId from cache file
        $contents = @file_get_contents($cacheFile);
        $lastOrgUnitId = is_string($contents) ? intval($contents) : -1;

        // If start OrgUnitId is not specified, use the "next" OrgUnitId
        if ($startOrgUnitId === null) {
            $startOrgUnitId = $lastOrgUnitId + 1;
        }

        // Get all area course offerings for the specified semester beginning with the start OrgUnitId
        $areaCourseOfferings = ($this->getCourseOfferings)(
            $semesterCode,
            $startOrgUnitId
        );

        $total = array_sum(array_map(
            fn(array $v) => count($v),
            $areaCourseOfferings
        ));

        $this->logger?->info(
            "<ImportOutcomesIntoCourses> " . $this->formatLogResults([
                "SemesterCode" => $semesterCode,
                "StartOrgUnitId" => $startOrgUnitId,
                "LastOrgUnitId" => $lastOrgUnitId,
                "Total" => $total
            ])
        );

        try {
            // For each area
            foreach ($areaCourseOfferings as $areaName => $courses) {
                // Get the outcome registry for the area
                $areaRegistry = ($this->getAreaRegistry)($areaName);

                // For each course offering in area
                foreach ($courses as $courseId) {
                    $courseId = intval($courseId);

                    // Fetch the courses's current outcomes and merge area outcomes into it
                    $courseRegistry = $this->outcomesAPI
                        ->getRegistry($this->outcomesAPI->getRegistryId($courseId))
                        ->merge($areaRegistry);

                    // Save results in D2L
                    $this->outcomesAPI->importObjectives(
                        $courseRegistry->id,
                        $courseRegistry->objectives
                    );

                    $this->logger?->info(
                        "<ImportOutcomesIntoCourses> " . $this->formatLogResults([
                            "Area" => $areaName,
                            "CourseId" => $courseId
                        ])
                    );

                    $imported++;

                    // Set last OrgUnitId to the org unit just processed
                    if ($courseId > $lastOrgUnitId) {
                        $lastOrgUnitId = $courseId;
                    }
                }
            }
        } finally {
            // If any org units have been save, save the last OrgUnitId to the cache file
            if ($lastOrgUnitId >= $startOrgUnitId) {
                @file_put_contents($cacheFile, $lastOrgUnitId);
            }

            $this->logger?->info(
                "<ImportOutcomesIntoCourses> " . $this->formatLogResults([
                    "SemesterCode" => $semesterCode,
                    "StartOrgUnitId" => $startOrgUnitId,
                    "LastOrgUnitId" => $lastOrgUnitId,
                    "Total" => $total,
                    "Imported" => $imported
                ])
            );
        }

        return static::SUCCESS;
    }
}
