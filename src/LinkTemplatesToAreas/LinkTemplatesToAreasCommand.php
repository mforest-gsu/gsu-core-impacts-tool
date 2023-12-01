<?php

declare(strict_types=1);

namespace GSU\CoreIMPACTS\LinkTemplatesToAreas;

use GSU\CoreIMPACTS\LinkTemplatesToAreas\Actions\GetTemplateAreaMap;
use GSU\D2L\API\OrgUnits\OrgUnitsAPI;
use mjfklib\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'templates:link-areas')]
class LinkTemplatesToAreasCommand extends Command
{
    public function __construct(
        private GetTemplateAreaMap $getTemplateAreaMap,
        private OrgUnitsAPI $orgUnitsAPI
    ) {
        parent::__construct(
            logStartFinish: true,
            logError: true,
        );
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
        $total = $linked = 0;

        $templateAreaMap = ($this->getTemplateAreaMap)();
        $total = array_sum(array_map(
            fn (array $v) => count($v),
            $templateAreaMap
        ));

        $this->logger?->info(
            "<LinkTemplatesToAreas> " . $this->formatLogResults([
                "Total" => $total
            ])
        );

        foreach ($templateAreaMap as $orgUnitId => $parentOrgUnitIds) {
            foreach ($parentOrgUnitIds as $parentOrgUnitId) {
                $this->orgUnitsAPI->addParent(
                    intval($orgUnitId),
                    intval($parentOrgUnitId)
                );

                $linked++;

                $this->logger?->info(
                    "<LinkTemplatesToAreas> " . $this->formatLogResults([
                        "TemplateId" => $orgUnitId,
                        "AreaId" => $parentOrgUnitId
                    ])
                );
            }
        }

        $this->logger?->info(
            "<LinkTemplatesToAreas> " . $this->formatLogResults([
                "Total" => $total,
                "Linked" => $linked
            ])
        );

        return static::SUCCESS;
    }
}
