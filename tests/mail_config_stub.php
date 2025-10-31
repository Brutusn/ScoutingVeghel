<?php
$SMTP_SERVER = getenv('SV_SMTP_HOST') ?: 'mailhog';
$SMTP_PORT = (int) (getenv('SV_SMTP_PORT') ?: 1025);
$SMTP_USER = getenv('SV_SMTP_USER') ?: '';
$SMTP_PASSWORD = getenv('SV_SMTP_PASSWORD') ?: '';
$SMTP_MAIL_FROM = getenv('SV_SMTP_FROM') ?: 'website@example.test';
