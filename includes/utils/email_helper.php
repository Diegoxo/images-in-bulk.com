<?php
/**
 * Advanced SMTP Email Helper (Pure PHP Implementation)
 * Provides professional-grade email delivery using SMTP with AUTH and STARTTLS.
 * No external libraries required.
 */

class EmailHelper
{

  /**
   * Send a sophisticated HTML email via SMTP.
   */
  public static function sendVerification($to, $name, $token)
  {
    $verifyLink = SITE_URL . "/verify-email?token=" . $token;
    $htmlContent = self::getTemplate($name, $verifyLink);
    $subject = "Verify your email - Images In Bulks";
    return self::send($to, $name, $subject, $htmlContent);
  }

  /**
   * Send a password reset email.
   */
  public static function sendPasswordReset($to, $name, $token)
  {
    $resetLink = SITE_URL . "/reset-password?token=" . $token;
    $htmlContent = self::getResetTemplate($name, $resetLink);
    $subject = "Reset your password - Images In Bulks";
    return self::send($to, $name, $subject, $htmlContent);
  }

  /**
   * Send an email change verification email.
   */
  public static function sendEmailChangeVerification($to, $name, $token)
  {
    $verifyLink = SITE_URL . "/verify-email-change.php?token=" . $token;
    $htmlContent = self::getEmailChangeTemplate($name, $verifyLink);
    $subject = "Verify your new email - Images In Bulks";
    return self::send($to, $name, $subject, $htmlContent);
  }

  /**
   * Send a payment confirmation email.
   */
  public static function sendPaymentSuccess($to, $name, $planName, $amount, $currency, $reference)
  {
    $htmlContent = self::getPaymentTemplate($name, $planName, $amount, $currency, $reference);
    $subject = "Payment Confirmed - Images In Bulks";
    return self::send($to, $name, $subject, $htmlContent);
  }

  /**
   * Internal SMTP sender core
   */
  private static function send($to, $name, $subject, $htmlContent)
  {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USERNAME;
    $pass = SMTP_PASSWORD;
    $fromEmail = SMTP_FROM_EMAIL;
    $fromName = SMTP_FROM_NAME;

    try {
      $socket = @fsockopen(($port == 465 ? "ssl://" : "") . $host, $port, $errno, $errstr, 15);
      if (!$socket)
        throw new Exception("Could not connect to SMTP host: $errstr");

      self::getResponse($socket, "220");
      fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
      self::getResponse($socket, "250");

      if ($port == 587) {
        fwrite($socket, "STARTTLS\r\n");
        self::getResponse($socket, "220");
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
          throw new Exception("Failed to enable crypto");
        }
        fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
        self::getResponse($socket, "250");
      }

      fwrite($socket, "AUTH LOGIN\r\n");
      self::getResponse($socket, "334");
      fwrite($socket, base64_encode($user) . "\r\n");
      self::getResponse($socket, "334");
      fwrite($socket, base64_encode($pass) . "\r\n");
      self::getResponse($socket, "235");

      fwrite($socket, "MAIL FROM: <$fromEmail>\r\n");
      self::getResponse($socket, "250");
      fwrite($socket, "RCPT TO: <$to>\r\n");
      self::getResponse($socket, "250");

      fwrite($socket, "DATA\r\n");
      self::getResponse($socket, "354");

      $headers = [
        "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
        "To: $name <$to>",
        "From: $fromName <$fromEmail>",
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "Date: " . date("r"),
        "X-Mailer: ImagesInBulk-v2"
      ];

      fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n");
      fwrite($socket, $htmlContent . "\r\n.\r\n");
      self::getResponse($socket, "250");

      fwrite($socket, "QUIT\r\n");
      fclose($socket);
      return true;
    } catch (Exception $e) {
      error_log("Elite SMTP Error sending to $to: " . $e->getMessage());
      return false;
    }
  }


  private static function getResetTemplate($name, $link)
  {
    return '
        <div style="background-color: #0f172a; color: #f8fafc; font-family: sans-serif; padding: 40px; text-align: center;">
            <div style="max-width: 600px; margin: 0 auto; background: #1e293b; border-radius: 20px; padding: 40px;">
                <h1 style="color: #a855f7;">Password Reset Request</h1>
                <p style="color: #cbd5e1;">Hi ' . htmlspecialchars($name) . ', we received a request to reset your password. Click the button below to continue.</p>
                <div style="margin: 30px 0;">
                    <a href="' . $link . '" style="display: inline-block; padding: 14px 32px; background: #9333ea; color: white; text-decoration: none; border-radius: 12px; font-weight: bold;">Reset Password</a>
                </div>
                <p style="font-size: 12px; color: #64748b;">If you didn\'t request this, you can safely ignore this email. This link will expire in 1 hour.</p>
            </div>
        </div>';
  }

  private static function getPaymentTemplate($name, $planName, $amount, $currency, $reference)
  {
    $formattedAmount = number_format($amount / 100, 2, '.', ',');
    return '
        <div style="background-color: #0f172a; color: #f8fafc; font-family: sans-serif; padding: 40px; text-align: center;">
            <div style="max-width: 600px; margin: 0 auto; background: #1e293b; border-radius: 24px; padding: 40px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                <div style="margin-bottom: 20px;">
                    <span style="background: linear-gradient(135deg, #22c55e 0%, #10b981 100%); padding: 8px 16px; border-radius: 100px; color: white; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;">Payment Successful</span>
                </div>
                <h1 style="color: white; font-size: 28px; margin-bottom: 10px;">Thank you for your purchase!</h1>
                <p style="color: #94a3b8; font-size: 15px;">Hi ' . htmlspecialchars($name) . ', your payment has been processed successfully. Your account has been updated with your new credits.</p>
                
                <div style="background: rgba(15, 23, 42, 0.4); border-radius: 16px; padding: 24px; margin: 32px 0; text-align: left; border: 1px solid rgba(255,255,255,0.03);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 12px;">
                        <span style="color: #64748b; font-size: 14px;">Plan / Item</span>
                        <span style="color: #f8fafc; font-weight: bold; font-size: 14px;">' . htmlspecialchars($planName) . '</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 12px;">
                        <span style="color: #64748b; font-size: 14px;">Amount Paid</span>
                        <span style="color: #f8fafc; font-weight: bold; font-size: 14px;">' . $currency . ' ' . $formattedAmount . '</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b; font-size: 14px;">Reference</span>
                        <span style="color: #94a3b8; font-family: monospace; font-size: 13px;">' . htmlspecialchars($reference) . '</span>
                    </div>
                </div>

                <div style="margin-top: 32px;">
                    <a href="' . SITE_URL . '/generator" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #9333ea 0%, #4f46e5 100%); color: white; text-decoration: none; border-radius: 14px; font-weight: bold; font-size: 16px; box-shadow: 0 10px 15px -3px rgba(147, 51, 234, 0.3);">Start Generating</a>
                </div>
                
                <p style="margin-top: 40px; font-size: 11px; color: #475569;">If you have any questions, please contact our support team.<br>&copy; ' . date('Y') . ' Images In Bulks. All rights reserved.</p>
            </div>
        </div>';
  }

  private static function getEmailChangeTemplate($name, $link)
  {
    return '
        <div style="background-color: #0f172a; color: #f8fafc; font-family: sans-serif; padding: 40px; text-align: center;">
            <div style="max-width: 600px; margin: 0 auto; background: #1e293b; border-radius: 20px; padding: 40px; border: 1px solid rgba(255,255,255,0.1);">
                <h1 style="color: #a855f7;">Email Change Verification</h1>
                <p style="color: #cbd5e1;">Hi ' . htmlspecialchars($name) . ', you have requested to change your email address. Please click the button below to verify and complete the change.</p>
                <div style="margin: 30px 0;">
                    <a href="' . $link . '" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #9333ea 0%, #4f46e5 100%); color: white; text-decoration: none; border-radius: 12px; font-weight: bold;">Verify New Email</a>
                </div>
                <p style="font-size: 12px; color: #64748b;">If you didn\'t request this change, please ignore this email. Your account is secure. This link will expire in 24 hours.</p>
                <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 30px 0;">
                <p style="font-size: 11px; color: #475569;">&copy; ' . date('Y') . ' Images In Bulk.</p>
            </div>
        </div>';
  }

  private static function getResponse($socket, $expectedCode)
  {
    $response = "";
    while ($line = fgets($socket, 515)) {
      $response .= $line;
      if (isset($line[3]) && $line[3] == " ")
        break;
    }
    if (substr($response, 0, 3) !== $expectedCode) {
      throw new Exception("SMTP Error: Expected $expectedCode but got " . substr($response, 0, 3) . " | Full: " . $response);
    }
    return $response;
  }

  private static function getTemplate($name, $link)
  {
    return '
        <div style="background-color: #0f172a; color: #f8fafc; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; text-align: center;">
            <div style="max-width: 600px; margin: 0 auto; background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 40px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
                <h1 style="background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 28px; margin-bottom: 20px;">Welcome aboard!</h1>
                <p style="font-size: 16px; line-height: 1.6; color: #cbd5e1;">Hi ' . htmlspecialchars($name) . ', we are excited to have you. Verify your email to start creating amazing images with AI.</p>
                <div style="margin: 30px 0;">
                    <a href="' . $link . '" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #9333ea 0%, #4f46e5 100%); color: white; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 16px; box-shadow: 0 10px 15px -3px rgba(147, 51, 234, 0.3);">Verify Email Address</a>
                </div>
                <p style="font-size: 13px; color: #64748b;">If the button doesn\'t work, copy this link: <br> ' . $link . '</p>
                <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 30px 0;">
                <p style="font-size: 12px; color: #475569;">&copy; ' . date('Y') . ' Images In Bulk. Premium AI Image Batching.</p>
            </div>
        </div>';
  }
}
