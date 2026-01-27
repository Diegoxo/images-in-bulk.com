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
    // 1. Fetch Configuration from .env/Constants
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USERNAME;
    $pass = SMTP_PASSWORD;
    $fromEmail = SMTP_FROM_EMAIL;
    $fromName = SMTP_FROM_NAME;

    // 2. Build Verification link
    $verifyLink = SITE_URL . "/auth/verify-email.php?token=" . $token;

    // 3. HTML Content (Premium Design)
    $htmlContent = self::getTemplate($name, $verifyLink);

    // 4. SMTP Protocol Logic
    try {
      $socket = @fsockopen(($port == 465 ? "ssl://" : "") . $host, $port, $errno, $errstr, 15);
      if (!$socket)
        throw new Exception("Could not connect to SMTP host: $errstr");

      self::getResponse($socket, "220");

      // HELLO
      fwrite($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
      self::getResponse($socket, "250");

      // STARTTLS if port is 587
      if ($port == 587) {
        fwrite($socket, "STARTTLS\r\n");
        self::getResponse($socket, "220");
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
          throw new Exception("Failed to enable crypto");
        }
        // Resend EHLO after TLS
        fwrite($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
        self::getResponse($socket, "250");
      }

      // AUTH
      fwrite($socket, "AUTH LOGIN\r\n");
      self::getResponse($socket, "334");
      fwrite($socket, base64_encode($user) . "\r\n");
      self::getResponse($socket, "334");
      fwrite($socket, base64_encode($pass) . "\r\n");
      self::getResponse($socket, "235");

      // MAIL FROM / RCPT TO
      fwrite($socket, "MAIL FROM: <$fromEmail>\r\n");
      self::getResponse($socket, "250");
      fwrite($socket, "RCPT TO: <$to>\r\n");
      self::getResponse($socket, "250");

      // DATA
      fwrite($socket, "DATA\r\n");
      self::getResponse($socket, "354");

      // HEADERS & CONTENT
      $headers = [
        "Subject: =?UTF-8?B?" . base64_encode("Verify your email - Images in Bulk") . "?=",
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

      // QUIT
      fwrite($socket, "QUIT\r\n");
      fclose($socket);
      return true;

    } catch (Exception $e) {
      error_log("Elite SMTP Error: " . $e->getMessage());
      return false;
    }
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
