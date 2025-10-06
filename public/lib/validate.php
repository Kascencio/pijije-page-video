<?php
/**
 * Validaciones centralizadas - sin duplicados
 */

if (!function_exists('validateDriveFileId')) {
  function validateDriveFileId(string $id): bool {
    return (bool)preg_match('/^[A-Za-z0-9_-]+$/', $id);
  }
}

if (!function_exists('validateEmail')) {
  /**
   * Devuelve email normalizado (lowercase, trim) o false si inválido
   */
  function validateEmail(string $email) {
    $email = trim(mb_strtolower($email));
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) return false;
    return $email;
  }
}

if (!function_exists('validatePassword')) {
  function validatePassword(string $pwd, int $minLen = 10): bool {
    if (mb_strlen($pwd) < $minLen) return false;
    return preg_match('/[a-z]/', $pwd) && preg_match('/[A-Z]/', $pwd) && preg_match('/\d/', $pwd);
  }
}

// Validar nombre (letras, espacios, apostrofes, puntos y guiones)
if (!function_exists('validateName')) {
  function validateName(string $name) {
    $name = trim($name);
    if ($name === '') return false;
    if (!preg_match("/^[\p{L} .'-]+$/u", $name)) return false;
    // Normalizar espacios múltiples
    $name = preg_replace('/\s+/', ' ', $name);
    return $name;
  }
}

// Validar datos de login (centralizado)
if (!function_exists('validateLoginData')) {
  function validateLoginData(array $data): array {
    $errors = [];
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    $validEmail = validateEmail($email);
    if ($email === '' || !$validEmail) {
      $errors['email'] = 'Email inválido';
    } else {
      $email = $validEmail; // usar el normalizado
    }
    if ($password === '') {
      $errors['password'] = 'Contraseña requerida';
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors,
      'data' => [
        'email' => $email,
        'password' => $password
      ]
    ];
  }
}

// Validar datos de registro (centralizado)
if (!function_exists('validateRegistrationData')) {
  function validateRegistrationData(array $data): array {
    $errors = [];
    $name = trim($data['name'] ?? '');
  $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $confirm = $data['confirm_password'] ?? '';

    if ($name === '' || !preg_match('/^[\p{L} .\'-]+$/u', $name)) {
      $errors['name'] = 'Nombre inválido';
    }
    $validEmail = validateEmail($email);
    if ($email === '' || !$validEmail) {
      $errors['email'] = 'Email inválido';
    } else {
      $email = $validEmail;
    }
    if ($password === '') {
      $errors['password'] = 'Contraseña requerida';
    } elseif (!validatePassword($password)) {
      $errors['password'] = 'La contraseña no cumple requisitos';
    }
    if ($confirm === '' || $confirm !== $password) {
      $errors['confirm_password'] = 'Las contraseñas no coinciden';
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors,
      'data' => [
        'name' => $name,
        'email' => $email,
        'password' => $password
      ]
    ];
  }
}
