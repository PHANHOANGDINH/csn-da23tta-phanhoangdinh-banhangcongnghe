<?php
// contact_send.php
session_start();

function backWith($ok, $msg){
  $_SESSION['contact_flash'] = ['ok'=>$ok, 'msg'=>$msg];
  header("Location: contact.php");
  exit;
}

// 1) Validate input
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $subject === '' || $message === '') {
  backWith(false, "Vui lòng điền đầy đủ: Họ tên, Email, Chủ đề, Nội dung.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  backWith(false, "Email không hợp lệ. Vui lòng kiểm tra lại.");
}

// 2) Load PHPMailer from your GitHub extracted folder
$phpMailerBase = __DIR__ . '/PHPMailer/src/';
if (!file_exists($phpMailerBase . 'PHPMailer.php')) {
  backWith(false, "Không tìm thấy PHPMailer. Hãy đảm bảo thư mục 'PHPMailer/src' nằm cùng cấp với file contact_send.php.");
}

require_once $phpMailerBase . 'Exception.php';
require_once $phpMailerBase . 'PHPMailer.php';
require_once $phpMailerBase . 'SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 3) Gmail SMTP config (App Password)
$gmailUser        = 'phanhoangdinh106@gmail.com';  // TODO: đổi email Gmail của bạn
$gmailAppPassword = 'cjpmzwjyhnsclqft';      // TODO: dán mật khẩu ứng dụng (16 ký tự)

if ($gmailAppPassword === 'PASTE_APP_PASSWORD_HERE') {
  backWith(false, "Bạn chưa dán Mật khẩu ứng dụng vào contact_send.php.");
}

try {
  $mail = new PHPMailer(true);

  // Debug khi cần (bật lên để xem lỗi SMTP rõ hơn)
  // $mail->SMTPDebug = 2;

  $mail->CharSet = 'UTF-8';
  $mail->isSMTP();
  $mail->Host       = 'smtp.gmail.com';
  $mail->SMTPAuth   = true;
  $mail->Username   = $gmailUser;
  $mail->Password   = $gmailAppPassword;

  // Gmail TLS
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
  $mail->Port       = 587;

  // Sender & recipient
  $mail->setFrom($gmailUser, 'TechStore Contact');
  $mail->addAddress($gmailUser, 'TechStore Admin'); // gửi về chính bạn (admin)

  // Reply-to để bạn bấm "Reply" sẽ trả lời khách
  $mail->addReplyTo($email, $name);

  // Subject
  $mail->Subject = "[TechStore] $subject";

  // Build body (HTML)
  $safePhone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
  $safeName  = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
  $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
  $safeSubj  = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
  $safeMsg   = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

  $mail->isHTML(true);
  $mail->Body = "
    <div style='font-family:Arial,sans-serif;font-size:14px;line-height:1.6;color:#0f172a'>
      <h2 style='margin:0 0 8px'>Liên hệ mới từ TechStore</h2>
      <div style='padding:12px;border:1px solid #e5e7eb;border-radius:12px;background:#f8fafc'>
        <p><b>Họ tên:</b> {$safeName}</p>
        <p><b>Email:</b> {$safeEmail}</p>
        <p><b>SĐT:</b> {$safePhone}</p>
        <p><b>Chủ đề:</b> {$safeSubj}</p>
        <p style='margin:12px 0 0'><b>Nội dung:</b><br>{$safeMsg}</p>
      </div>
      <p style='color:#6b7280;margin-top:10px'>Bạn có thể bấm Reply để trả lời trực tiếp khách hàng.</p>
    </div>
  ";

  // Plain text fallback
  $mail->AltBody =
    "Lien he moi tu TechStore\n" .
    "Ho ten: $name\nEmail: $email\nSDT: $phone\nChu de: $subject\n\nNoi dung:\n$message\n";

  $mail->send();
  backWith(true, "Đã gửi liên hệ thành công! TechStore sẽ phản hồi qua email sớm nhất.");

} catch (Exception $e) {
  // Lỗi cụ thể từ PHPMailer
  backWith(false, "SMTP lỗi: " . $mail->ErrorInfo);
} catch (\Throwable $e) {
  backWith(false, "Có lỗi xảy ra: " . $e->getMessage());
}
