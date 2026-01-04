<?php
// config/mail.php
// ✅ Dùng "MẬT KHẨU ỨNG DỤNG" (App Password) của Google, KHÔNG phải mật khẩu Gmail thường.

return [
  'from_email' => 'phanhoangdinh106@gmail.com',     // email gửi đi (Gmail)
  'from_name'  => 'TechStore Trà Vinh',             // tên hiển thị
  'to_email'   => 'phanhoangdinh106@gmail.com',     // email nhận liên hệ (có thể khác)
  'smtp_host'  => 'smtp.gmail.com',
  'smtp_user'  => 'phanhoangdinh106@gmail.com',
  'smtp_pass'  => 'cjpmzwjyhnsclqft', // ví dụ: abcd efgh ijkl mnop
  'smtp_port'  => 465,                               // 465 (SSL) hoặc 587 (TLS)
  'smtp_secure'=> 'ssl',                             // 'ssl' nếu 465, 'tls' nếu 587
];
