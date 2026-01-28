<?php
$pageTitle = 'Quoting Settings';
require_once 'includes/header.php';

$db = Database::getInstance();
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'save') {
        // Save settings
        $settings = [
            'company_name' => $_POST['company_name'] ?? '',
            'company_address' => $_POST['company_address'] ?? '',
            'company_phone' => $_POST['company_phone'] ?? '',
            'company_email' => $_POST['company_email'] ?? '',
            'company_website' => $_POST['company_website'] ?? '',
            'company_abn' => $_POST['company_abn'] ?? '',
            'labour_rate_standard' => (float)($_POST['labour_rate_standard'] ?? 75),
            'labour_rate_premium' => (float)($_POST['labour_rate_premium'] ?? 95),
            'gst_rate' => (float)($_POST['gst_rate'] ?? 15),
            'quote_validity_days' => (int)($_POST['quote_validity_days'] ?? 30),
            'invoice_payment_terms' => $_POST['invoice_payment_terms'] ?? 'Net 14',
            // Foam settings
            'foam_default_sheet_area' => (float)($_POST['foam_default_sheet_area'] ?? 3.91),
            'foam_markup_multiplier' => (float)($_POST['foam_markup_multiplier'] ?? 2),
            'foam_cutting_fee_percent' => (float)($_POST['foam_cutting_fee_percent'] ?? 15),
            'quote_terms' => $_POST['quote_terms'] ?? '',
            'quote_footer_text' => $_POST['quote_footer_text'] ?? '',
            // SMTP settings
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
            'smtp_username' => $_POST['smtp_username'] ?? '',
            'smtp_secure' => $_POST['smtp_secure'] ?? 'tls',
            'smtp_from_email' => $_POST['smtp_from_email'] ?? '',
        ];

        // Handle SMTP password separately (only update if provided)
        if (!empty($_POST['smtp_password'])) {
            $settings['smtp_password'] = $_POST['smtp_password'];
        }

        foreach ($settings as $key => $value) {
            $existing = $db->fetchOne("SELECT id FROM settings WHERE setting_key = :key", ['key' => $key]);
            if ($existing) {
                $db->update('settings', ['setting_value' => $value], 'setting_key = :key', ['key' => $key]);
            } else {
                $db->insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
            }
        }

        Auth::logAudit('settings', 0, 'UPDATE', ['quoting_settings' => 'updated']);
        $message = 'Settings saved successfully!';
        $messageType = 'success';
    }

    if ($action === 'uploadLogo') {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = $_FILES['logo']['type'];

            if (!in_array($fileType, $allowedTypes)) {
                $message = 'Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.';
                $messageType = 'danger';
            } else {
                $maxSize = 2 * 1024 * 1024; // 2MB
                if ($_FILES['logo']['size'] > $maxSize) {
                    $message = 'File too large. Maximum size is 2MB.';
                    $messageType = 'danger';
                } else {
                    // Get file extension
                    $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                    $filename = 'company_logo.' . strtolower($ext);
                    $uploadPath = 'uploads/logos/' . $filename;
                    $fullPath = dirname(__FILE__) . '/' . $uploadPath;

                    // Delete old logo if exists
                    $oldLogo = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'company_logo_path'");
                    if ($oldLogo && $oldLogo['setting_value'] && file_exists($oldLogo['setting_value'])) {
                        @unlink($oldLogo['setting_value']);
                    }

                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $fullPath)) {
                        // Save path to settings
                        $existing = $db->fetchOne("SELECT id FROM settings WHERE setting_key = 'company_logo_path'");
                        if ($existing) {
                            $db->update('settings', ['setting_value' => $uploadPath], "setting_key = 'company_logo_path'");
                        } else {
                            $db->insert('settings', ['setting_key' => 'company_logo_path', 'setting_value' => $uploadPath]);
                        }

                        Auth::logAudit('settings', 0, 'UPDATE', ['logo' => 'uploaded']);
                        $message = 'Logo uploaded successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to upload logo. Please check folder permissions.';
                        $messageType = 'danger';
                    }
                }
            }
        } else {
            $message = 'Please select a logo file to upload.';
            $messageType = 'warning';
        }
    }

    if ($action === 'deleteLogo') {
        $logoPath = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'company_logo_path'");
        if ($logoPath && $logoPath['setting_value']) {
            $fullPath = dirname(__FILE__) . '/' . $logoPath['setting_value'];
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
            $db->update('settings', ['setting_value' => ''], "setting_key = 'company_logo_path'");
            Auth::logAudit('settings', 0, 'UPDATE', ['logo' => 'deleted']);
            $message = 'Logo deleted successfully!';
            $messageType = 'success';
        }
    }
}

// Load current settings
function getSetting($db, $key, $default = '') {
    $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = :key", ['key' => $key]);
    return $result ? $result['setting_value'] : $default;
}

$companyName = getSetting($db, 'company_name', '');
$companyAddress = getSetting($db, 'company_address', '');
$companyPhone = getSetting($db, 'company_phone', '');
$companyEmail = getSetting($db, 'company_email', '');
$companyWebsite = getSetting($db, 'company_website', '');
$companyAbn = getSetting($db, 'company_abn', '');
$companyLogoPath = getSetting($db, 'company_logo_path', '');
$labourRateStandard = getSetting($db, 'labour_rate_standard', '75');
$labourRatePremium = getSetting($db, 'labour_rate_premium', '95');
$gstRate = getSetting($db, 'gst_rate', '15');
$quoteValidityDays = getSetting($db, 'quote_validity_days', '30');
$invoicePaymentTerms = getSetting($db, 'invoice_payment_terms', 'Net 14');
$quoteTerms = getSetting($db, 'quote_terms', "1. This quote is valid for 30 days from the date of issue.\n2. A 50% deposit is required to commence work.\n3. Final payment is due upon completion.\n4. Prices are inclusive of GST where shown.\n5. Any variations to the quoted scope may result in price adjustments.");
$quoteFooterText = getSetting($db, 'quote_footer_text', '');

// Foam settings
$foamDefaultSheetArea = getSetting($db, 'foam_default_sheet_area', '3.91');
$foamMarkupMultiplier = getSetting($db, 'foam_markup_multiplier', '2');
$foamCuttingFeePercent = getSetting($db, 'foam_cutting_fee_percent', '15');

// SMTP settings
$smtpHost = getSetting($db, 'smtp_host', '');
$smtpPort = getSetting($db, 'smtp_port', '587');
$smtpUsername = getSetting($db, 'smtp_username', '');
$smtpSecure = getSetting($db, 'smtp_secure', 'tls');
$smtpFromEmail = getSetting($db, 'smtp_from_email', '');
$smtpConfigured = !empty($smtpHost) && !empty($smtpFromEmail);
?>

<div class="page-header">
    <h1 class="page-title">Quoting Settings</h1>
    <div class="page-actions">
        <a href="quotes.php" class="btn btn-secondary">Back to Quotes</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="settings-container">
    <!-- Company Logo Section -->
    <div class="settings-section">
        <h3>Company Logo</h3>
        <div class="logo-upload-area">
            <?php if ($companyLogoPath && file_exists($companyLogoPath)): ?>
                <div class="current-logo">
                    <img src="<?php echo htmlspecialchars($companyLogoPath); ?>?t=<?php echo time(); ?>" alt="Company Logo" style="max-width: 200px; max-height: 100px;">
                    <form method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="action" value="deleteLogo">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete current logo?');">Remove Logo</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="no-logo">
                    <p style="color: #666;">No logo uploaded</p>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="logo-form">
                <input type="hidden" name="action" value="uploadLogo">
                <div class="form-group">
                    <label for="logo">Upload New Logo</label>
                    <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control">
                    <small class="form-text">Recommended size: 200x80 pixels. Max 2MB. Formats: JPG, PNG, GIF, WebP</small>
                </div>
                <button type="submit" class="btn btn-primary">Upload Logo</button>
            </form>
        </div>
    </div>

    <!-- Company Details Section -->
    <form method="POST" class="settings-form">
        <input type="hidden" name="action" value="save">

        <div class="settings-section">
            <h3>Company Details</h3>
            <p class="section-description">These details appear on quotes and invoices</p>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" class="form-control"
                        value="<?php echo htmlspecialchars($companyName); ?>" placeholder="Your Company Name">
                </div>
                <div class="form-group col-md-6">
                    <label for="company_abn">GST Number</label>
                    <input type="text" id="company_abn" name="company_abn" class="form-control"
                        value="<?php echo htmlspecialchars($companyAbn); ?>" placeholder="12 345 678 901">
                </div>
            </div>

            <div class="form-group">
                <label for="company_address">Address</label>
                <textarea id="company_address" name="company_address" class="form-control" rows="2"
                    placeholder="123 Main Street, City, State 1234"><?php echo htmlspecialchars($companyAddress); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="company_phone">Phone</label>
                    <input type="text" id="company_phone" name="company_phone" class="form-control"
                        value="<?php echo htmlspecialchars($companyPhone); ?>" placeholder="(02) 1234 5678">
                </div>
                <div class="form-group col-md-4">
                    <label for="company_email">Email</label>
                    <input type="email" id="company_email" name="company_email" class="form-control"
                        value="<?php echo htmlspecialchars($companyEmail); ?>" placeholder="info@yourcompany.com">
                </div>
                <div class="form-group col-md-4">
                    <label for="company_website">Website</label>
                    <input type="url" id="company_website" name="company_website" class="form-control"
                        value="<?php echo htmlspecialchars($companyWebsite); ?>" placeholder="https://www.yourcompany.com">
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>Rates & Charges</h3>

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="labour_rate_standard">Standard Labour Rate ($/hr)</label>
                    <input type="number" id="labour_rate_standard" name="labour_rate_standard" class="form-control"
                        value="<?php echo htmlspecialchars($labourRateStandard); ?>" min="0" step="0.01">
                </div>
                <div class="form-group col-md-3">
                    <label for="labour_rate_premium">Premium Labour Rate ($/hr)</label>
                    <input type="number" id="labour_rate_premium" name="labour_rate_premium" class="form-control"
                        value="<?php echo htmlspecialchars($labourRatePremium); ?>" min="0" step="0.01">
                </div>
                <div class="form-group col-md-3">
                    <label for="gst_rate">GST Rate (%)</label>
                    <input type="number" id="gst_rate" name="gst_rate" class="form-control"
                        value="<?php echo htmlspecialchars($gstRate); ?>" min="0" max="100" step="0.1">
                </div>
                <div class="form-group col-md-3">
                    <label for="quote_validity_days">Quote Validity (days)</label>
                    <input type="number" id="quote_validity_days" name="quote_validity_days" class="form-control"
                        value="<?php echo htmlspecialchars($quoteValidityDays); ?>" min="1" max="365">
                </div>
            </div>

            <div class="form-group">
                <label for="invoice_payment_terms">Invoice Payment Terms</label>
                <select id="invoice_payment_terms" name="invoice_payment_terms" class="form-control" style="max-width: 200px;">
                    <option value="Due on Receipt" <?php echo $invoicePaymentTerms === 'Due on Receipt' ? 'selected' : ''; ?>>Due on Receipt</option>
                    <option value="Net 7" <?php echo $invoicePaymentTerms === 'Net 7' ? 'selected' : ''; ?>>Net 7</option>
                    <option value="Net 14" <?php echo $invoicePaymentTerms === 'Net 14' ? 'selected' : ''; ?>>Net 14</option>
                    <option value="Net 30" <?php echo $invoicePaymentTerms === 'Net 30' ? 'selected' : ''; ?>>Net 30</option>
                    <option value="Net 60" <?php echo $invoicePaymentTerms === 'Net 60' ? 'selected' : ''; ?>>Net 60</option>
                </select>
            </div>
        </div>

        <div class="settings-section">
            <h3>Foam Pricing</h3>
            <p class="section-description">Settings for foam cost calculations in the quote builder</p>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="foam_default_sheet_area">Default Sheet Area (m²)</label>
                    <input type="number" id="foam_default_sheet_area" name="foam_default_sheet_area" class="form-control"
                        value="<?php echo htmlspecialchars($foamDefaultSheetArea); ?>" min="0.01" step="0.01">
                    <small class="form-text">Standard sheet size for cost calculations (typically 3.91 m²)</small>
                </div>
                <div class="form-group col-md-4">
                    <label for="foam_markup_multiplier">Markup Multiplier</label>
                    <input type="number" id="foam_markup_multiplier" name="foam_markup_multiplier" class="form-control"
                        value="<?php echo htmlspecialchars($foamMarkupMultiplier); ?>" min="1" step="0.1">
                    <small class="form-text">Multiply cost per m² by this value (e.g., 2 = 100% markup)</small>
                </div>
                <div class="form-group col-md-4">
                    <label for="foam_cutting_fee_percent">Cutting Fee (%)</label>
                    <input type="number" id="foam_cutting_fee_percent" name="foam_cutting_fee_percent" class="form-control"
                        value="<?php echo htmlspecialchars($foamCuttingFeePercent); ?>" min="0" max="100" step="0.1">
                    <small class="form-text">Additional fee when cutting is required</small>
                </div>
            </div>

            <div class="foam-formula-preview">
                <strong>Formula Preview:</strong>
                <code>Sell Price = (Sheet Cost ÷ <?php echo $foamDefaultSheetArea; ?>) × <?php echo $foamMarkupMultiplier; ?> × Sq Meters × (1 + <?php echo $foamCuttingFeePercent; ?>% if cutting)</code>
            </div>
        </div>

        <div class="settings-section">
            <h3>Quote PDF Settings</h3>

            <div class="form-group">
                <label for="quote_terms">Terms & Conditions</label>
                <textarea id="quote_terms" name="quote_terms" class="form-control" rows="6"
                    placeholder="Enter your standard terms and conditions..."><?php echo htmlspecialchars($quoteTerms); ?></textarea>
                <small class="form-text">These terms appear at the bottom of quote PDFs. One term per line.</small>
            </div>

            <div class="form-group">
                <label for="quote_footer_text">Footer Contact Text</label>
                <textarea id="quote_footer_text" name="quote_footer_text" class="form-control" rows="2"
                    placeholder="Thank you for your business! Contact us at..."><?php echo htmlspecialchars($quoteFooterText); ?></textarea>
                <small class="form-text">Custom message shown at the very bottom of the PDF with contact details.</small>
            </div>
        </div>

        <div class="settings-section">
            <h3>Email Settings (SMTP)</h3>
            <p class="section-description">Configure SMTP to send quotes and invoices via email</p>

            <?php if ($smtpConfigured): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    <strong>Email is configured</strong> - SMTP settings are ready for sending emails.
                </div>
            <?php else: ?>
                <div class="alert alert-warning" style="margin-bottom: 20px;">
                    <strong>Email not configured</strong> - Complete the settings below to enable email sending.
                </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="smtp_host">SMTP Host</label>
                    <input type="text" id="smtp_host" name="smtp_host" class="form-control"
                        value="<?php echo htmlspecialchars($smtpHost); ?>" placeholder="smtp.gmail.com">
                    <small class="form-text">e.g., smtp.gmail.com, smtp.office365.com, mail.yourdomain.com</small>
                </div>
                <div class="form-group col-md-3">
                    <label for="smtp_port">SMTP Port</label>
                    <input type="number" id="smtp_port" name="smtp_port" class="form-control"
                        value="<?php echo htmlspecialchars($smtpPort); ?>" placeholder="587">
                    <small class="form-text">Usually 587 (TLS) or 465 (SSL)</small>
                </div>
                <div class="form-group col-md-3">
                    <label for="smtp_secure">Security</label>
                    <select id="smtp_secure" name="smtp_secure" class="form-control">
                        <option value="tls" <?php echo $smtpSecure === 'tls' ? 'selected' : ''; ?>>TLS (Recommended)</option>
                        <option value="ssl" <?php echo $smtpSecure === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        <option value="none" <?php echo $smtpSecure === 'none' ? 'selected' : ''; ?>>None</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="smtp_username">SMTP Username</label>
                    <input type="text" id="smtp_username" name="smtp_username" class="form-control"
                        value="<?php echo htmlspecialchars($smtpUsername); ?>" placeholder="your-email@gmail.com">
                    <small class="form-text">Usually your full email address</small>
                </div>
                <div class="form-group col-md-6">
                    <label for="smtp_password">SMTP Password</label>
                    <input type="password" id="smtp_password" name="smtp_password" class="form-control"
                        placeholder="<?php echo $smtpConfigured ? '••••••••' : 'Enter password or app password'; ?>">
                    <small class="form-text">For Gmail, use an App Password. Leave blank to keep existing password.</small>
                </div>
            </div>

            <div class="form-group">
                <label for="smtp_from_email">From Email Address</label>
                <input type="email" id="smtp_from_email" name="smtp_from_email" class="form-control" style="max-width: 400px;"
                    value="<?php echo htmlspecialchars($smtpFromEmail); ?>" placeholder="quotes@yourcompany.com">
                <small class="form-text">The email address that will appear in the "From" field</small>
            </div>

            <div class="smtp-test-section">
                <button type="button" id="testSmtpBtn" class="btn btn-secondary" <?php echo !$smtpConfigured ? 'disabled' : ''; ?>>
                    Test SMTP Connection
                </button>
                <span id="smtpTestResult" style="margin-left: 15px;"></span>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">Save Settings</button>
        </div>
    </form>
</div>

<style>
.settings-container {
    max-width: 900px;
}

.settings-section {
    background: white;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.settings-section h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.section-description {
    color: #666;
    margin-bottom: 20px;
    font-size: 14px;
}

.form-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.form-row .form-group {
    flex: 1;
    min-width: 200px;
}

.col-md-3 { flex: 0 0 calc(25% - 15px); min-width: 150px; }
.col-md-4 { flex: 0 0 calc(33.333% - 14px); min-width: 180px; }
.col-md-6 { flex: 0 0 calc(50% - 10px); min-width: 250px; }

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

textarea.form-control {
    resize: vertical;
    font-family: inherit;
}

.form-text {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #666;
}

.logo-upload-area {
    display: flex;
    gap: 30px;
    align-items: flex-start;
    flex-wrap: wrap;
}

.current-logo {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.current-logo img {
    display: block;
    margin-bottom: 10px;
}

.no-logo {
    width: 200px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border: 2px dashed #ddd;
    border-radius: 8px;
}

.logo-form {
    flex: 1;
    min-width: 250px;
}

.logo-form input[type="file"] {
    padding: 8px;
    background: #f8f9fa;
}

.form-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.btn-lg {
    padding: 12px 30px;
    font-size: 16px;
}

.smtp-test-section {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.foam-formula-preview {
    margin-top: 15px;
    padding: 12px 15px;
    background: #f8f9fa;
    border-radius: 4px;
    font-size: 13px;
}

.foam-formula-preview code {
    display: inline-block;
    margin-left: 10px;
    padding: 4px 8px;
    background: #e9ecef;
    border-radius: 3px;
    font-family: monospace;
    color: #495057;
}

.alert {
    padding: 12px 16px;
    border-radius: 4px;
    font-size: 14px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
    .form-row .form-group {
        flex: 0 0 100%;
    }
    .col-md-3, .col-md-4, .col-md-6 {
        flex: 0 0 100%;
    }
    .logo-upload-area {
        flex-direction: column;
    }
}
</style>

<script>
document.getElementById('testSmtpBtn')?.addEventListener('click', async function() {
    const btn = this;
    const resultSpan = document.getElementById('smtpTestResult');

    btn.disabled = true;
    btn.textContent = 'Testing...';
    resultSpan.innerHTML = '';

    try {
        const response = await fetch('api/testSmtp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });

        const data = await response.json();

        if (data.success) {
            resultSpan.innerHTML = '<span style="color: green;">✓ ' + data.message + '</span>';
        } else {
            resultSpan.innerHTML = '<span style="color: red;">✗ ' + data.message + '</span>';
        }
    } catch (error) {
        resultSpan.innerHTML = '<span style="color: red;">✗ Connection test failed</span>';
    }

    btn.disabled = false;
    btn.textContent = 'Test SMTP Connection';
});
</script>

<?php require_once 'includes/footer.php'; ?>
