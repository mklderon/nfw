<?php

namespace Core\Validation;

use Core\Exceptions\ValidationException;

class Validator {
    private $rules;
    private $messages;

    public function __construct(array $rules = [], array $messages = []) {
        $this->rules = $rules;
        $this->messages = $messages;
    }

    public function validate(array $data) {
        $errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                [$ruleName, $param] = $this->parseRule($rule);

                switch ($ruleName) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field][] = $this->getMessage($field, 'required', "El campo $field es requerido");
                        }
                        break;
                    case 'email':
                        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = $this->getMessage($field, 'email', "El campo $field debe ser un email válido");
                        }
                        break;
                    case 'min':
                        if ($value && strlen($value) < (int)$param) {
                            $errors[$field][] = $this->getMessage($field, 'min', "El campo $field debe tener al menos $param caracteres");
                        }
                        break;
                    case 'max':
                        if ($value && strlen($value) > (int)$param) {
                            $errors[$field][] = $this->getMessage($field, 'max', "El campo $field no puede exceder los $param caracteres");
                        }
                        break;
                    // Más reglas aquí
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function parseRule($rule): array {
        return strpos($rule, ':') !== false ? explode(':', $rule) : [$rule, null];
    }

    private function getMessage(string $field, string $rule, string $default): string {
        return $this->messages[$field][$rule] ?? $default;
    }
}