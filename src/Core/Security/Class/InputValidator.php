<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Security\Class;

/**
 * AUTOSAV — Validateur d'inputs
 */
class InputValidator
{
    /** Résultats de validation ['field' => ['error1', ...]] */
    private array $errors = [];

    public function required(string $field, mixed $value, string $label = ''): self
    {
        $label = $label ?: $field;
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->errors[$field][] = "Le champ « {$label} » est obligatoire.";
        }
        return $this;
    }

    public function email(string $field, mixed $value, string $label = ''): self
    {
        $label = $label ?: $field;
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "« {$label} » n'est pas une adresse email valide.";
        }
        return $this;
    }

    public function minLength(string $field, string $value, int $min, string $label = ''): self
    {
        $label = $label ?: $field;
        if (mb_strlen($value) < $min) {
            $this->errors[$field][] = "« {$label} » doit contenir au moins {$min} caractères.";
        }
        return $this;
    }

    public function maxLength(string $field, string $value, int $max, string $label = ''): self
    {
        $label = $label ?: $field;
        if (mb_strlen($value) > $max) {
            $this->errors[$field][] = "« {$label} » ne doit pas dépasser {$max} caractères.";
        }
        return $this;
    }

    public function isInt(string $field, mixed $value, string $label = ''): self
    {
        $label = $label ?: $field;
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->errors[$field][] = "« {$label} » doit être un entier.";
        }
        return $this;
    }

    public function inArray(string $field, mixed $value, array $allowed, string $label = ''): self
    {
        $label = $label ?: $field;
        if (!in_array($value, $allowed, true)) {
            $this->errors[$field][] = "« {$label} » contient une valeur non autorisée.";
        }
        return $this;
    }

    public function hasErrors(): bool  { return !empty($this->errors); }
    public function getErrors(): array { return $this->errors; }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field][] = $message;
        return $this;
    }

    public function merge(self $other): self
    {
        foreach ($other->getErrors() as $field => $msgs) {
            foreach ($msgs as $msg) {
                $this->errors[$field][] = $msg;
            }
        }
        return $this;
    }
}
