<?php
namespace App\Core;

/**
 * Tiny server-side validator. Returns errors keyed by field.
 */
class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): array
    {
        foreach ($rules as $field => $ruleStr) {
            $rulesList = explode('|', $ruleStr);
            $value = $data[$field] ?? null;
            foreach ($rulesList as $rule) {
                $this->applyRule($field, $value, $rule, $data);
            }
        }
        return $this->errors;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, $value, string $rule, array $data): void
    {
        if (strpos($rule, ':') !== false) {
            [$name, $param] = explode(':', $rule, 2);
        } else {
            $name = $rule;
            $param = null;
        }

        switch ($name) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->add($field, 'поле обязательно для заполнения');
                }
                break;
            case 'min':
                if ($value !== null && $value !== '' && mb_strlen((string)$value) < (int)$param) {
                    $this->add($field, "минимум $param символов");
                }
                break;
            case 'max':
                if ($value !== null && mb_strlen((string)$value) > (int)$param) {
                    $this->add($field, "максимум $param символов");
                }
                break;
            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->add($field, 'некорректный email');
                }
                break;
            case 'in':
                $allowed = explode(',', (string)$param);
                if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
                    $this->add($field, 'недопустимое значение');
                }
                break;
            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->add($field, 'значение должно быть числом');
                }
                break;
            case 'int':
                if ($value !== null && $value !== '' && !ctype_digit((string)$value)) {
                    $this->add($field, 'значение должно быть целым числом');
                }
                break;
        }
    }

    private function add(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }
}
