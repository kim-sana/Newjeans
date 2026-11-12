<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<string>> $rules
     * @return array<string, string> First error message per field
     */
    public static function make(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $definition) {
            $fieldRules = is_array($definition) ? $definition : explode('|', (string) $definition);
            $fieldRules = array_values(array_filter(array_map('trim', $fieldRules), static fn (string $rule): bool => $rule !== ''));

            $value = $data[$field] ?? null;
            $isNumeric = in_array('numeric', $fieldRules, true) || in_array('integer', $fieldRules, true);
            $isEmpty = self::isEmpty($value);

            if ($isEmpty) {
                if (in_array('required', $fieldRules, true)) {
                    $errors[$field] = self::label($field) . ' is required.';
                }

                continue;
            }

            foreach ($fieldRules as $rule) {
                if ($rule === 'required' || $rule === 'nullable') {
                    continue;
                }

                $error = self::check($field, $value, $rule, $isNumeric);

                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }

        return $errors;
    }

    private static function check(string $field, mixed $value, string $rule, bool $isNumeric): ?string
    {
        $label = self::label($field);
        [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

        return match ($name) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                ? $label . ' must be a valid email address.'
                : null,
            'url' => filter_var($value, FILTER_VALIDATE_URL) === false
                ? $label . ' must be a valid URL.'
                : null,
            'numeric' => !is_numeric($value)
                ? $label . ' must be a number.'
                : null,
            'integer' => filter_var($value, FILTER_VALIDATE_INT) === false
                ? $label . ' must be an integer.'
                : null,
            'date' => is_string($value) && strtotime($value) === false
                ? $label . ' must be a valid date.'
                : null,
            'min' => $parameter !== null && self::compare($value, $parameter, $isNumeric, '<')
                ? $label . ' must be at least ' . $parameter . '.'
                : null,
            'max' => $parameter !== null && self::compare($value, $parameter, $isNumeric, '>')
                ? $label . ' may not be greater than ' . $parameter . '.'
                : null,
            'in' => $parameter !== null && !in_array((string) $value, explode(',', $parameter), true)
                ? $label . ' is invalid.'
                : null,
            default => null,
        };
    }

    private static function compare(mixed $value, string $parameter, bool $isNumeric, string $operator): bool
    {
        if ($isNumeric) {
            return $operator === '<'
                ? (float) $value < (float) $parameter
                : (float) $value > (float) $parameter;
        }

        $length = function_exists('mb_strlen') ? mb_strlen((string) $value) : strlen((string) $value);

        return $operator === '<'
            ? $length < (int) $parameter
            : $length > (int) $parameter;
    }

    private static function isEmpty(mixed $value): bool
    {
        return $value === null
            || $value === ''
            || (is_string($value) && trim($value) === '')
            || (is_array($value) && $value === []);
    }

    private static function label(string $field): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $field));
    }
}
