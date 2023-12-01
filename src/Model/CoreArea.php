<?php

declare(strict_types=1);

namespace GSU\CoreIMPACTS\Model;

use mjfklib\Container\ArrayValue;
use mjfklib\Container\ObjectFactory;
use GSU\D2L\API\Outcomes\Model\OutcomeDetails;

class CoreArea
{
    /**
     * @param mixed $values
     * @param array<string,OutcomeDetails> $outcomes
     * @param OutcomeDetails $rootOutcome
     * @return self
     */
    public static function create(
        mixed $values,
        array $outcomes,
        OutcomeDetails $rootOutcome
    ): self {
        return ObjectFactory::createObject(
            $values,
            self::class,
            fn (array $values): self => self::construct(
                $values,
                $outcomes,
                $rootOutcome
            )
        );
    }


    /**
     * @param mixed[] $values
     * @param array<string,OutcomeDetails> $outcomes
     * @param OutcomeDetails $rootOutcome
     * @return self
     */
    private static function construct(
        array $values,
        array $outcomes,
        OutcomeDetails $rootOutcome
    ): self {
        $areaOutcomeNames = ArrayValue::getStringArray(ArrayValue::getArray($values, 'outcomes'));

        return new self(
            id: ArrayValue::getInt($values, 'id'),
            code: ArrayValue::getString($values, 'code'),
            name: ArrayValue::getString($values, 'name'),
            templates: ArrayValue::getStringArray(ArrayValue::getArray($values, 'templates')),
            outcomes: [
                new OutcomeDetails(
                    id: $rootOutcome->id,
                    description: $rootOutcome->description,
                    children: array_values(array_filter(
                        $outcomes,
                        fn (OutcomeDetails $v) => in_array(explode(':', $v->description)[0], $areaOutcomeNames, true)
                    ))
                )
            ]
        );
    }


    /**
     * @param int $id
     * @param string $code
     * @param string $name
     * @param string[] $templates
     * @param array<int,OutcomeDetails> $outcomes
     */
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public array $templates,
        public array $outcomes
    ) {
    }
}
