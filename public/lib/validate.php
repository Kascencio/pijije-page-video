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
  function validateEmail(string $email): bool {
    $email = trim(mb_strtolower($email));
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
  }
}

if (!function_exists('validatePassword')) {
  function validatePassword(string $pwd, int $minLen = 10): bool {
    if (mb_strlen($pwd) < $minLen) return false;
    return preg_match('/[a-z]/', $pwd) && preg_match('/[A-Z]/', $pwd) && preg_match('/\d/', $pwd);
  }
}
