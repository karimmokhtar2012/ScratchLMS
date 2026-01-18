<?php

declare(strict_types=1);

namespace App\Services;

class ContentRuleService
{
    public function getRuleCatalog(): array
    {
        return [
            'unlock_after_days' => [
                'version' => 1,
                'params' => [
                    'days' => ['type' => 'int', 'min' => 0],
                ],
                'ui' => [
                    'label' => 'Unlock after days',
                    'fields' => [
                        ['name' => 'days', 'type' => 'number', 'min' => 0],
                    ],
                ],
            ],
            'unlock_after_item_completion' => [
                'version' => 1,
                'params' => [
                    'item_id' => ['type' => 'int', 'min' => 1],
                ],
                'ui' => [
                    'label' => 'Unlock after item completion',
                    'fields' => [
                        ['name' => 'item_id', 'type' => 'number', 'min' => 1],
                    ],
                ],
            ],
            'unlock_after_percent' => [
                'version' => 1,
                'params' => [
                    'percent' => ['type' => 'int', 'min' => 0, 'max' => 100],
                ],
                'ui' => [
                    'label' => 'Unlock after progress percent',
                    'fields' => [
                        ['name' => 'percent', 'type' => 'number', 'min' => 0, 'max' => 100],
                    ],
                ],
            ],
        ];
    }

    public function validateRuleRecord(array $rule): array
    {
        $ruleType = (string) ($rule['rule_type'] ?? '');
        $paramsJson = (string) ($rule['params_json'] ?? '');
        if ($ruleType === '' || $paramsJson === '') {
            return [false, ['Missing rule_type or params_json']];
        }

        $params = json_decode($paramsJson, true);
        if (!is_array($params)) {
            return [false, ['Invalid params_json']];
        }

        return $this->validateRuleParams($ruleType, $params);
    }

    public function validateRuleParams(string $ruleType, array $params): array
    {
        $catalog = $this->getRuleCatalog();
        if (!isset($catalog[$ruleType])) {
            return [false, ["Unknown rule_type: {$ruleType}"]];
        }

        $definition = $catalog[$ruleType];
        $errors = [];

        foreach ($definition['params'] as $key => $constraints) {
            if (!array_key_exists($key, $params)) {
                $errors[] = "Missing param: {$key}";
                continue;
            }

            $value = $params[$key];
            if ($constraints['type'] === 'int') {
                if (!is_int($value)) {
                    $errors[] = "Param {$key} must be int";
                    continue;
                }
            }

            if (isset($constraints['min']) && is_int($value) && $value < $constraints['min']) {
                $errors[] = "Param {$key} must be >= {$constraints['min']}";
            }

            if (isset($constraints['max']) && is_int($value) && $value > $constraints['max']) {
                $errors[] = "Param {$key} must be <= {$constraints['max']}";
            }
        }

        return [empty($errors), $errors];
    }
}
