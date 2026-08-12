<?php

declare(strict_types=1);

namespace App\Validators;

use App\Core\Database;
use App\Core\Session;

/**
 * Fluent server-side input validator.
 *
 * Usage:
 *   $validator = (new Validator($data))->required('name')->email('email')->min('age', 18);
 *   if ($validator->fails()) { ... $validator->errors(); }
 */
final class Validator
{
    private array $data;
    private array $errors = [];
    private array $rules = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    private function value(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function required(string ...$fields): self
    {
        foreach ($fields as $field) {
            $value = $this->value($field);
            if ($value === null || (is_string($value) && trim($value) === '') || (is_array($value) && count($value) === 0)) {
                $this->addError($field, 'The ' . str_replace('_', ' ', $field) . ' field is required.');
            }
        }
        return $this;
    }

    public function email(string $field): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'The ' . str_replace('_', ' ', $field) . ' must be a valid email address.');
        }
        return $this;
    }

    public function integer(string $field): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, 'The ' . str_replace('_', ' ', $field) . ' must be a whole number.');
        }
        return $this;
    }

    public function numeric(string $field): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, 'The ' . str_replace('_', ' ', $field) . ' must be a number.');
        }
        return $this;
    }

    public function min(string $field, float $min): self
    {
        $value = $this->value($field);
        if (is_numeric($value) && (float) $value < $min) {
            $this->addError($field, 'The ' . str_replace('_', ' ', $field) . ' must be at least ' . $min . '.');
        }
        return $this;
    }

    public function max(string $field, float $max): self
    {
        $value = $this->value($field);
        if (is_numeric($value) && (float) $value > $max) {
            $this->addError($field, 'The ' . str_replace('_', ' ', $field) . ' must not exceed ' . $max . '.');
        }
        return $this;
    }

    public function maxLength(string $field, int $max): self
    {
        $value = $this->value($field);
        if (is_string($value) && mb_strlen($value) > $max) {
            $this->addError($field, 'The ' . str_replace('_', ' ', $field) . ' must not exceed ' . $max . ' characters.');
        }
        return $this;
    }

    public function date(string $field): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', (string) $value);
            if (!$d || $d->format('Y-m-d') !== $value) {
                $this->addError($field, 'The ' . str_replace('_', ' ', $field) . ' must be a valid date (YYYY-MM-DD).');
            }
        }
        return $this;
    }

    public function in(string $field, array $allowed): self
    {
        $value = $this->value($field);
        if ($value === null || $value === '') {
            return $this;
        }
        foreach ($allowed as $allowedValue) {
            if ((string) $allowedValue === (string) $value) {
                return $this;
            }
        }
        $this->addError($field, 'The selected ' . str_replace('_', ' ', $field) . ' is invalid.');
        return $this;
    }

    public function unique(string $field, string $table, ?string $column = null, ?int $ignoreId = null, string $ignoreColumn = 'id'): self
    {
        $value = $this->value($field);
        if ($value === null || $value === '') {
            return $this;
        }
        $column = $column ?? $field;
        $sql = "SELECT COUNT(*) AS c FROM `{$table}` WHERE `{$column}` = ?";
        $params = [$value];
        if ($ignoreId !== null) {
            $sql .= " AND `{$ignoreColumn}` != ?";
            $params[] = $ignoreId;
        }
        $count = (int) Database::fetch($sql, $params)['c'];
        if ($count > 0) {
            $this->addError($field, 'The ' . str_replace('_', ' ', $field) . ' has already been taken.');
        }
        return $this;
    }

    public function exists(string $field, string $table, string $column = 'id'): self
    {
        $value = $this->value($field);
        if ($value === null || $value === '') {
            return $this;
        }
        $sql = "SELECT COUNT(*) AS c FROM `{$table}` WHERE `{$column}` = ? AND deleted_at IS NULL";
        $count = (int) Database::fetch($sql, [$value])['c'];
        if ($count === 0) {
            $this->addError($field, 'The selected ' . str_replace('_', ' ', $field) . ' does not exist.');
        }
        return $this;
    }

    public function confirmed(string $field): self
    {
        $value = $this->value($field);
        $confirmation = $this->value($field . '_confirmation');
        if ($value !== null && $value !== $confirmation) {
            $this->addError($field, 'The ' . str_replace('_', ' ', $field) . ' confirmation does not match.');
        }
        return $this;
    }

    public function password(string $field): self
    {
        $value = (string) ($this->value($field) ?? '');
        if ($value !== '' && (mb_strlen($value) < 8 || !preg_match('/[A-Z]/', $value) || !preg_match('/[0-9]/', $value))) {
            $this->addError($field, 'The password must be at least 8 characters with at least one uppercase letter and one number.');
        }
        return $this;
    }

    public function arrayOf(string $field, string $subRule = 'integer'): self
    {
        $value = $this->value($field);
        if (!is_array($value)) {
            $this->addError($field, 'The ' . str_replace('_', ' ', $field) . ' must be an array.');
            return $this;
        }
        foreach ($value as $item) {
            if ($subRule === 'integer' && filter_var($item, FILTER_VALIDATE_INT) === false) {
                $this->addError($field, 'Invalid selection in ' . str_replace('_', ' ', $field) . '.');
                break;
            }
        }
        return $this;
    }

    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    public function passes(): bool
    {
        return !$this->fails();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Flatten errors into a single message list.
     */
    public function messages(): array
    {
        $messages = [];
        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $message) {
                $messages[] = $message;
            }
        }
        return $messages;
    }

    /**
     * Convenience: validate and redirect back with errors on failure.
     */
    public function validateOrFail(?string $redirectTo = null): void
    {
        if ($this->fails()) {
            Session::flashErrors($this->errors());
            Session::flashOldInput($_POST);
            flash('error', 'Please correct the highlighted fields.');
            redirect($redirectTo ?? ($_SERVER['HTTP_REFERER'] ?? url('')));
        }
    }
}
