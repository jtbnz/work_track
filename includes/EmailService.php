<?php
/**
 * Email Service for sending quotes and invoices
 * Uses PHPMailer for SMTP email delivery
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/models/Quote.php';
require_once __DIR__ . '/pdf/QuotePdf.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $db;
    private $mailer;
    private $lastError = '';

    // SMTP settings
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $smtpSecure;
    private $fromEmail;
    private $fromName;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->loadSettings();
        $this->initMailer();
    }

    /**
     * Load SMTP settings from database
     */
    private function loadSettings(): void
    {
        $this->smtpHost = $this->getSetting('smtp_host', '');
        $this->smtpPort = (int) $this->getSetting('smtp_port', 587);
        $this->smtpUsername = $this->getSetting('smtp_username', '');
        $this->smtpPassword = $this->getSetting('smtp_password', '');
        $this->smtpSecure = $this->getSetting('smtp_secure', 'tls');
        $this->fromEmail = $this->getSetting('smtp_from_email', '');
        $this->fromName = $this->getSetting('company_name', 'WorkTrack');
    }

    /**
     * Get a setting value from the database
     */
    private function getSetting(string $key, string $default = ''): string
    {
        $result = $this->db->fetchOne(
            "SELECT setting_value FROM settings WHERE setting_key = ?",
            [$key]
        );
        return $result ? $result['setting_value'] : $default;
    }

    /**
     * Initialize PHPMailer with SMTP settings
     */
    private function initMailer(): void
    {
        $this->mailer = new PHPMailer(true);

        // Only configure SMTP if host is set
        if (!empty($this->smtpHost)) {
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->smtpHost;
            $this->mailer->Port = $this->smtpPort;

            if (!empty($this->smtpUsername)) {
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = $this->smtpUsername;
                $this->mailer->Password = $this->smtpPassword;
            }

            if ($this->smtpSecure === 'tls') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($this->smtpSecure === 'ssl') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
        }

        $this->mailer->isHTML(true);
        $this->mailer->CharSet = 'UTF-8';
    }

    /**
     * Check if SMTP is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->smtpHost) && !empty($this->fromEmail);
    }

    /**
     * Get the last error message
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Send a quote email with PDF attachment
     *
     * @param int $quoteId The quote ID
     * @param string $toEmail Recipient email address
     * @param string $subject Email subject (optional, will be auto-generated if empty)
     * @param string $message Custom message body (optional)
     * @param bool $updateStatus Whether to update quote status to 'sent'
     * @return bool Success status
     */
    public function sendQuote(int $quoteId, string $toEmail, string $subject = '', string $message = '', bool $updateStatus = true): bool
    {
        try {
            // Get quote data
            $quoteModel = new Quote();
            $quote = $quoteModel->getById($quoteId);
            if (!$quote) {
                $this->lastError = 'Quote not found';
                return false;
            }

            // Get client data
            $client = $this->db->fetchOne(
                "SELECT * FROM clients WHERE id = ?",
                [$quote['client_id']]
            );

            if (!$client) {
                $this->lastError = 'Client not found';
                return false;
            }

            // Use client email if no email provided
            if (empty($toEmail)) {
                $toEmail = $client['email'] ?? '';
            }

            if (empty($toEmail)) {
                $this->lastError = 'No recipient email address provided';
                return false;
            }

            // Check SMTP configuration
            if (!$this->isConfigured()) {
                $this->lastError = 'SMTP settings are not configured. Please configure email settings in Quoting Settings.';
                return false;
            }

            // Generate PDF
            $quotePdf = new QuotePdf($quote);
            $pdfContent = $quotePdf->Output('S'); // Return as string

            // Build default subject if not provided
            if (empty($subject)) {
                $subject = "Quote {$quote['quote_number']} from {$this->fromName}";
            }

            // Build default message if not provided
            if (empty($message)) {
                $message = $this->buildDefaultQuoteMessage($quote, $client);
            }

            // Reset mailer for new email
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Set email details
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            $this->mailer->addAddress($toEmail, $client['name'] ?? '');
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $message;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));

            // Attach PDF
            $filename = "Quote_{$quote['quote_number']}.pdf";
            $this->mailer->addStringAttachment($pdfContent, $filename, 'base64', 'application/pdf');

            // Send email
            $this->mailer->send();

            // Update quote status to 'sent' if requested and currently draft
            if ($updateStatus && $quote['status'] === 'draft') {
                $quoteModel->updateStatus($quoteId, 'sent');
            }

            // Log the email send
            $this->logEmailSent($quoteId, 'quote', $toEmail, $subject);

            return true;

        } catch (Exception $e) {
            $this->lastError = $this->mailer->ErrorInfo;
            error_log("Email send failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build default quote email message
     */
    private function buildDefaultQuoteMessage(array $quote, array $client): string
    {
        $clientName = $client['name'] ?? 'Valued Customer';
        $quoteNumber = $quote['quote_number'];
        $expiryDate = date('d/m/Y', strtotime($quote['expiry_date']));
        $total = number_format($quote['total_incl_gst'], 2);
        $companyName = $this->fromName;
        $companyPhone = $this->getSetting('company_phone', '');
        $companyEmail = $this->fromEmail;

        $message = "
        <html>
        <body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\">
            <p>Dear {$clientName},</p>

            <p>Please find attached your quote <strong>{$quoteNumber}</strong> from {$companyName}.</p>

            <p><strong>Quote Summary:</strong></p>
            <ul>
                <li>Quote Number: {$quoteNumber}</li>
                <li>Total (inc GST): \${$total}</li>
                <li>Valid Until: {$expiryDate}</li>
            </ul>

            <p>Please review the attached PDF for full details.</p>

            <p>If you have any questions or would like to proceed, please don't hesitate to contact us.</p>

            <p>Kind regards,<br>
            {$companyName}";

        if (!empty($companyPhone)) {
            $message .= "<br>Phone: {$companyPhone}";
        }
        if (!empty($companyEmail)) {
            $message .= "<br>Email: {$companyEmail}";
        }

        $message .= "</p>
        </body>
        </html>";

        return $message;
    }

    /**
     * Log email sent to audit
     */
    private function logEmailSent(int $documentId, string $type, string $toEmail, string $subject): void
    {
        try {
            $this->db->insert('audit_log', [
                'table_name' => $type === 'quote' ? 'quotes' : 'invoices',
                'record_id' => $documentId,
                'action' => 'email_sent',
                'changes' => json_encode([
                    'to' => $toEmail,
                    'subject' => $subject,
                    'sent_at' => date('Y-m-d H:i:s')
                ]),
                'user_id' => $_SESSION['user_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the email send
            error_log("Failed to log email send: " . $e->getMessage());
        }
    }

    /**
     * Test SMTP connection
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'SMTP settings are not configured'
            ];
        }

        try {
            // Create a new PHPMailer instance for testing
            $testMailer = new PHPMailer(true);
            $testMailer->isSMTP();
            $testMailer->Host = $this->smtpHost;
            $testMailer->Port = $this->smtpPort;
            $testMailer->SMTPDebug = SMTP::DEBUG_OFF;

            if (!empty($this->smtpUsername)) {
                $testMailer->SMTPAuth = true;
                $testMailer->Username = $this->smtpUsername;
                $testMailer->Password = $this->smtpPassword;
            }

            if ($this->smtpSecure === 'tls') {
                $testMailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($this->smtpSecure === 'ssl') {
                $testMailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            // Try to connect
            if ($testMailer->smtpConnect()) {
                $testMailer->smtpClose();
                return [
                    'success' => true,
                    'message' => 'SMTP connection successful'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to connect to SMTP server'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'SMTP connection failed: ' . $e->getMessage()
            ];
        }
    }
}
