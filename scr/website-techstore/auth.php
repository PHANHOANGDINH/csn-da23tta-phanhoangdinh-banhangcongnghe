<?php
session_start();

/**
 * File JSON lưu tài khoản
 */
const USERS_FILE = __DIR__ . '/data/users.json';

/** ✅ Helper escape (tránh redeclare) */
if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

/** ✅ Validate email */
function isValidEmail($email) {
  $email = trim((string)$email);
  if ($email === '') return false;
  return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** =========================
 * JSON Helpers
========================= */
function saveUsersToJson($users) {
  file_put_contents(
    USERS_FILE,
    json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
  );
}

function loadUsersFromJson() {
  if (!file_exists(USERS_FILE)) return [];

  $json = file_get_contents(USERS_FILE);
  $data = json_decode($json, true);
  if (!is_array($data)) return [];

  $changed = false;

  foreach ($data as &$user) {
    // migrate password plain -> hash
    if (isset($user['password']) && !isset($user['password_hash'])) {
      $user['password_hash'] = password_hash($user['password'], PASSWORD_DEFAULT);
      unset($user['password']);
      $changed = true;
    }

    // chuẩn hoá key
    if (!isset($user['role'])) $user['role'] = 'user';
    if (!isset($user['username'])) $user['username'] = '';
    if (!isset($user['email'])) $user['email'] = '';
  }
  unset($user);

  if ($changed) saveUsersToJson($data);

  return $data;
}

/** =========================
 * FIND USER
========================= */

/** (GIỮ LẠI) Find theo username (legacy) */
function findUser($username, $password) {
  $users = loadUsersFromJson();
  foreach ($users as $user) {
    if (($user['username'] ?? '') !== $username) continue;

    if (isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
      return $user;
    } elseif (isset($user['password']) && $user['password'] === $password) {
      return $user;
    }
  }
  return null;
}

/** ✅ Find theo email */
function findUserByEmail($email, $password) {
  $users = loadUsersFromJson();
  $email = strtolower(trim((string)$email));

  foreach ($users as $user) {
    $uEmail = strtolower(trim((string)($user['email'] ?? '')));
    if ($uEmail === '' || $uEmail !== $email) continue;

    if (isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
      return $user;
    } elseif (isset($user['password']) && $user['password'] === $password) {
      return $user;
    }
  }
  return null;
}

/** =========================
 * EXISTS CHECK
========================= */
function userExists($username) {
  foreach (loadUsersFromJson() as $user) {
    if (($user['username'] ?? '') === $username) return true;
  }
  return false;
}

function emailExists($email) {
  $email = strtolower(trim((string)$email));
  foreach (loadUsersFromJson() as $user) {
    $uEmail = strtolower(trim((string)($user['email'] ?? '')));
    if ($uEmail !== '' && $uEmail === $email) return true;
  }
  return false;
}

/** =========================
 * CREATE USER
========================= */

/** Legacy create theo username */
function createUser($username, $password, $role = 'user') {
  $users = loadUsersFromJson();

  $users[] = [
    'email'         => '',
    'username'      => $username,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role'          => $role
  ];

  saveUsersToJson($users);
}

/** ✅ Create theo email (login = email), username = tên hiển thị */
function createUserWithEmail($email, $username, $password, $role = 'user') {
  $users = loadUsersFromJson();

  $users[] = [
    'email'         => trim((string)$email),
    'username'      => $username,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role'          => $role
  ];

  saveUsersToJson($users);
}

/** =========================
 * DELETE / ROLE
========================= */

/** ✅ Xoá theo email (an toàn hơn khi login bằng email) */
function deleteUserByEmail($email) {
  $email = strtolower(trim((string)$email));
  if ($email === '') return false;

  $users = loadUsersFromJson();
  $new = [];
  $deleted = false;

  foreach ($users as $user) {
    $uEmail = strtolower(trim((string)($user['email'] ?? '')));
    if ($uEmail !== '' && $uEmail === $email) {
      // chặn admin
      if (($user['role'] ?? '') === 'admin') return false;
      $deleted = true;
      continue;
    }
    $new[] = $user;
  }

  if ($deleted) saveUsersToJson($new);
  return $deleted;
}

/** ✅ Đổi role theo email */
function changeUserRoleByEmail($email, $role) {
  $email = strtolower(trim((string)$email));
  if ($email === '') return false;

  if (!in_array($role, ['user','admin'], true)) return false;

  $users = loadUsersFromJson();
  $updated = false;

  foreach ($users as &$user) {
    $uEmail = strtolower(trim((string)($user['email'] ?? '')));
    if ($uEmail !== '' && $uEmail === $email) {
      // chặn đổi role của admin (để khỏi tự phá)
      if (($user['role'] ?? '') === 'admin') return false;
      $user['role'] = $role;
      $updated = true;
      break;
    }
  }
  unset($user);

  if ($updated) saveUsersToJson($users);
  return $updated;
}

/** =========================
 * AUTH SESSION
========================= */
function loginUser($user) {
  $_SESSION['user'] = [
    'email'    => $user['email'] ?? null,
    'username' => $user['username'] ?? null, // ✅ tên hiển thị
    'role'     => $user['role'] ?? 'user'
  ];
}

function logoutUser() {
  $_SESSION = [];

  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"], $params["domain"],
      $params["secure"], $params["httponly"]
    );
  }

  session_destroy();
}

function currentUser() {
  return $_SESSION['user'] ?? null;
}

function isLoggedIn() {
  return currentUser() !== null;
}

function isAdmin() {
  $u = currentUser();
  return $u && (($u['role'] ?? '') === 'admin');
}

function requireLogin() {
  if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
  }
}

function requireAdmin() {
  if (!isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo "<h1>403 - Không có quyền truy cập</h1>";
    echo '<p><a href="index.php">Về trang chủ</a></p>';
    exit;
  }
}
