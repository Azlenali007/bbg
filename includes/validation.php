<?php
declare(strict_types=1);

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $ruleList = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($ruleList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                switch ($rule) {
                    case 'required':
                        if ($value === null || trim((string)$value) === '') {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                        }
                        break;

                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($field, 'Please enter a valid email address.');
                        }
                        break;

                    case 'username':
                        if (!empty($value) && !preg_match('/^[a-zA-Z0-9_]{3,30}$/', (string)$value)) {
                            $this->addError($field, 'Username must be 3-30 characters with letters, numbers, and underscores only.');
                        }
                        break;

                    case 'min':
                        $min = (int)($params[0] ?? 0);
                        if (is_numeric($value) && (float)$value < $min) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min}.");
                        } elseif (is_string($value) && mb_strlen($value) < $min) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min} characters.");
                        }
                        break;

                    case 'max':
                        $max = (int)($params[0] ?? 0);
                        if (is_numeric($value) && (float)$value > $max) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " cannot exceed {$max}.");
                        } elseif (is_string($value) && mb_strlen($value) > $max) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " cannot exceed {$max} characters.");
                        }
                        break;

                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be a valid number.');
                        }
                        break;

                    case 'integer':
                        if (!empty($value) && filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be a whole integer.');
                        }
                        break;

                    case 'url':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                            $this->addError($field, 'Please enter a valid URL.');
                        }
                        break;

                    case 'in':
                        if (!empty($value) && !in_array((string)$value, $params, true)) {
                            $this->addError($field, 'Selected ' . str_replace('_', ' ', $field) . ' is invalid.');
                        }
                        break;
                }
            }
        }

        return empty($this->errors);
    }

    public function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
