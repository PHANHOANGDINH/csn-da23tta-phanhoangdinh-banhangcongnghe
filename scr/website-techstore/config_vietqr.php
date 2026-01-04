<?php
// ==============================
//  CẤU HÌNH VIETQR (API)
// ==============================
// Bạn lấy Client ID + API Key từ VietQR/Casso (nếu bạn dùng API tạo ảnh QR).
// Nếu chưa có, vẫn tạo file này trước, sau đó điền sau.

const VIETQR_CLIENT_ID = 'YOUR_CLIENT_ID';
const VIETQR_API_KEY   = 'YOUR_API_KEY';

// ==============================
//  THÔNG TIN NHẬN TIỀN TECHSTORE
// ==============================
// acqId = BIN ngân hàng (VD: Vietcombank 970436, ACB 970416, MB 970422, Techcombank 970407, ...)
// Bạn thay đúng ngân hàng của bạn.
const TECHSTORE_BANK_BIN = '970403'; // <-- đổi theo ngân hàng của bạn
const TECHSTORE_ACCOUNT  = '070145453842'; // <-- số tài khoản của bạn
const TECHSTORE_NAME     = 'PHAN HOANG DINH'; // <-- tên chủ tài khoản (không dấu cũng được)

// template: compact | compact2 | qr_only | print
const TECHSTORE_QR_TEMPLATE = 'compact';

// Prefix nội dung chuyển khoản để đối soát
const TECHSTORE_ADDINFO_PREFIX = 'TECHSTORE';
