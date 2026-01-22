<?php
/**
 * Quote PDF Generator
 *
 * Generates professional PDF quotes using TCPDF
 */

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/db.php';

class QuotePdf extends TCPDF {
    private $companyName;
    private $companyAddress;
    private $companyPhone;
    private $companyEmail;
    private $companyWebsite;
    private $companyAbn;
    private $logoPath;
    private $quoteTerms;
    private $quoteFooterText;

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4') {
        parent::__construct($orientation, $unit, $format, true, 'UTF-8', false);

        // Load company settings
        $db = Database::getInstance();
        $this->companyName = $this->getSetting($db, 'company_name', 'WorkTrack');
        $this->companyAddress = $this->getSetting($db, 'company_address', '');
        $this->companyPhone = $this->getSetting($db, 'company_phone', '');
        $this->companyEmail = $this->getSetting($db, 'company_email', '');
        $this->companyWebsite = $this->getSetting($db, 'company_website', '');
        $this->companyAbn = $this->getSetting($db, 'company_abn', '');
        $this->logoPath = $this->getSetting($db, 'company_logo_path', '');
        $this->quoteTerms = $this->getSetting($db, 'quote_terms', "1. This quote is valid for 30 days from the date of issue.\n2. A 50% deposit is required to commence work.\n3. Final payment is due upon completion.\n4. Prices are inclusive of GST where shown.\n5. Any variations to the quoted scope may result in price adjustments.");
        $this->quoteFooterText = $this->getSetting($db, 'quote_footer_text', '');

        // Set document properties
        $this->SetCreator('WorkTrack');
        $this->SetAuthor($this->companyName);
        $this->SetTitle('Quote');

        // Set default monospaced font
        $this->SetDefaultMonospacedFont('courier');

        // Set margins (tighter for A4 fit)
        $this->SetMargins(12, 35, 12);
        $this->SetHeaderMargin(8);
        $this->SetFooterMargin(20);

        // Set auto page breaks
        $this->SetAutoPageBreak(TRUE, 25);

        // Set font
        $this->SetFont('helvetica', '', 10);
    }

    private function getSetting($db, $key, $default = '') {
        $result = $db->fetchOne(
            "SELECT setting_value FROM settings WHERE setting_key = :key",
            ['key' => $key]
        );
        return $result ? $result['setting_value'] : $default;
    }

    public function Header() {
        // Company logo (if exists)
        $logoFullPath = dirname(dirname(__DIR__)) . '/' . $this->logoPath;
        if ($this->logoPath && file_exists($logoFullPath)) {
            $this->Image($logoFullPath, 12, 8, 40, 0, '', '', 'T', false, 300, '', false, false, 0);
            $this->SetY(8);
            $this->SetX(55);
        } else {
            $this->SetY(8);
        }

        // Company name
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 6, $this->companyName, 0, 1, 'R');

        // Company details - combine on fewer lines
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(100, 100, 100);
        if ($this->companyAddress) {
            $this->Cell(0, 3.5, $this->companyAddress, 0, 1, 'R');
        }
        $contactLine = [];
        if ($this->companyPhone) $contactLine[] = 'Ph: ' . $this->companyPhone;
        if ($this->companyEmail) $contactLine[] = $this->companyEmail;
        if (count($contactLine) > 0) {
            $this->Cell(0, 3.5, implode('  |  ', $contactLine), 0, 1, 'R');
        }
        if ($this->companyWebsite || $this->companyAbn) {
            $extraLine = [];
            if ($this->companyWebsite) $extraLine[] = $this->companyWebsite;
            if ($this->companyAbn) $extraLine[] = 'ABN: ' . $this->companyAbn;
            $this->Cell(0, 3.5, implode('  |  ', $extraLine), 0, 1, 'R');
        }

        $this->SetTextColor(0, 0, 0);

        // Line separator
        $this->SetY(32);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(12, 32, 198, 32);
    }

    public function Footer() {
        // Compact footer
        $this->SetY(-18);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(12, $this->GetY(), 198, $this->GetY());

        $this->Ln(2);
        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(80, 80, 80);

        // Custom footer text (if any)
        if ($this->quoteFooterText) {
            $this->SetFont('helvetica', 'I', 7);
            $this->Cell(0, 3, $this->quoteFooterText, 0, 1, 'C');
        }

        // Page number and generation date
        $this->SetFont('helvetica', 'I', 7);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 4, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages() . '  |  Generated ' . date('d/m/Y H:i'), 0, 0, 'C');
    }

    /**
     * Generate PDF from quote data
     */
    public function generate($quote, $outputPath = null) {
        $this->SetTitle('Quote ' . $quote['quote_number']);

        // Add page
        $this->AddPage();

        // Quote title
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 7, 'QUOTE', 0, 1, 'L');

        // Quote details section
        $this->SetFont('helvetica', '', 9);
        $this->Ln(2);

        // Two-column layout for quote info and client info
        $startY = $this->GetY();

        // Left column - Quote details
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(90, 5, 'Quote Details', 0, 1);
        $this->SetFont('helvetica', '', 9);

        $this->Cell(25, 4, 'Quote No:', 0, 0);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(60, 4, $quote['quote_number'] . ($quote['revision'] > 1 ? ' Rev ' . $quote['revision'] : ''), 0, 1);
        $this->SetFont('helvetica', '', 9);

        $this->Cell(25, 4, 'Date:', 0, 0);
        $this->Cell(60, 4, date('d/m/Y', strtotime($quote['quote_date'])), 0, 1);

        $this->Cell(25, 4, 'Valid Until:', 0, 0);
        $this->Cell(60, 4, date('d/m/Y', strtotime($quote['expiry_date'])), 0, 1);

        // Right column - Client details
        $this->SetXY(110, $startY);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(85, 5, 'Bill To', 0, 1);
        $this->SetX(110);
        $this->SetFont('helvetica', '', 9);

        if (!empty($quote['client_name'])) {
            $this->SetX(110);
            $this->SetFont('helvetica', 'B', 9);
            $this->Cell(85, 4, $quote['client_name'], 0, 1);
            $this->SetFont('helvetica', '', 9);
        }
        if (!empty($quote['client_address'])) {
            $this->SetX(110);
            $this->MultiCell(85, 4, $quote['client_address'], 0, 'L');
        }
        if (!empty($quote['client_phone'])) {
            $this->SetX(110);
            $this->Cell(85, 4, 'Ph: ' . $quote['client_phone'], 0, 1);
        }
        if (!empty($quote['client_email'])) {
            $this->SetX(110);
            $this->Cell(85, 4, $quote['client_email'], 0, 1);
        }

        // Project reference (if linked)
        if (!empty($quote['project_title'])) {
            $this->Ln(3);
            $this->SetFont('helvetica', 'B', 9);
            $this->Cell(20, 4, 'Project:', 0, 0);
            $this->SetFont('helvetica', '', 9);
            $this->Cell(0, 4, $quote['project_title'], 0, 1);
        }

        // Special instructions
        if (!empty($quote['special_instructions'])) {
            $this->Ln(3);
            $this->SetFont('helvetica', 'B', 9);
            $this->Cell(0, 4, 'Special Instructions:', 0, 1);
            $this->SetFont('helvetica', '', 8);
            $this->SetFillColor(248, 249, 250);
            $this->MultiCell(0, 4, $quote['special_instructions'], 0, 'L', true);
        }

        $this->Ln(5);

        // Foam section
        if (!empty($quote['foam_items']) && count($quote['foam_items']) > 0) {
            $this->renderFoamTable($quote['foam_items']);
            $this->Ln(3);
        }

        // Materials section
        if (!empty($quote['materials']) && count($quote['materials']) > 0) {
            $this->renderMaterialsTable($quote['materials']);
            $this->Ln(3);
        }

        // Misc charges section (only if any included)
        $includedMisc = array_filter($quote['misc_items'] ?? [], function($item) {
            return $item['included'];
        });

        if (count($includedMisc) > 0) {
            $this->renderMiscTable($includedMisc);
            $this->Ln(3);
        }

        // Labour section
        if ($this->hasLabour($quote)) {
            $this->renderLabourTable($quote);
            $this->Ln(3);
        }

        // Totals section
        $this->renderTotals($quote);

        // Terms and conditions
        if ($this->quoteTerms) {
            $this->Ln(6);
            $this->SetFont('helvetica', 'B', 8);
            $this->Cell(0, 4, 'Terms & Conditions', 0, 1);
            $this->SetFont('helvetica', '', 7);
            $this->SetTextColor(80, 80, 80);
            $this->MultiCell(0, 3, $this->quoteTerms, 0, 'L');
            $this->SetTextColor(0, 0, 0);
        }

        // Output
        if ($outputPath) {
            $this->Output($outputPath, 'F');
            return $outputPath;
        }

        return $this->Output('quote_' . $quote['quote_number'] . '.pdf', 'S');
    }

    private function renderMaterialsTable($materials) {
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(52, 58, 64);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 6, 'Materials', 0, 1, 'L', true);

        // Table header
        $this->SetFont('helvetica', 'B', 8);
        $this->SetFillColor(233, 236, 239);
        $this->SetTextColor(0, 0, 0);

        $this->Cell(95, 5, 'Description', 1, 0, 'L', true);
        $this->Cell(22, 5, 'Qty', 1, 0, 'C', true);
        $this->Cell(30, 5, 'Unit Price', 1, 0, 'R', true);
        $this->Cell(39, 5, 'Total', 1, 1, 'R', true);

        // Table rows
        $this->SetFont('helvetica', '', 8);
        $fill = false;
        $this->SetFillColor(248, 249, 250);

        foreach ($materials as $material) {
            $description = $material['item_description'];
            if (!empty($material['manufacturers_code'])) {
                $description .= ' (' . $material['manufacturers_code'] . ')';
            }

            $this->Cell(95, 5, $this->truncate($description, 55), 1, 0, 'L', $fill);
            $this->Cell(22, 5, number_format($material['quantity'], 2), 1, 0, 'C', $fill);
            $this->Cell(30, 5, '$' . number_format($material['unit_cost'], 2), 1, 0, 'R', $fill);
            $this->Cell(39, 5, '$' . number_format($material['line_total'], 2), 1, 1, 'R', $fill);

            $fill = !$fill;
        }

        // Materials subtotal
        $this->SetFont('helvetica', 'B', 8);
        $materialsTotal = array_sum(array_column($materials, 'line_total'));
        $this->Cell(147, 5, 'Materials Subtotal:', 1, 0, 'R');
        $this->Cell(39, 5, '$' . number_format($materialsTotal, 2), 1, 1, 'R');
    }

    private function renderFoamTable($foamItems) {
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(52, 58, 64);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 6, 'Foam', 0, 1, 'L', true);

        // Table header
        $this->SetFont('helvetica', 'B', 8);
        $this->SetFillColor(233, 236, 239);
        $this->SetTextColor(0, 0, 0);

        $this->Cell(62, 5, 'Description', 1, 0, 'L', true);
        $this->Cell(25, 5, 'Area (m²)', 1, 0, 'C', true);
        $this->Cell(20, 5, 'Cutting', 1, 0, 'C', true);
        $this->Cell(40, 5, 'Unit Price', 1, 0, 'R', true);
        $this->Cell(39, 5, 'Total', 1, 1, 'R', true);

        // Table rows
        $this->SetFont('helvetica', '', 8);
        $fill = false;
        $this->SetFillColor(248, 249, 250);

        foreach ($foamItems as $foam) {
            $description = $foam['grade_code'] . ' - ' . $foam['thickness'];
            $cuttingText = $foam['cutting_required'] ? 'Yes' : 'No';

            $this->Cell(62, 5, $description, 1, 0, 'L', $fill);
            $this->Cell(25, 5, number_format($foam['square_meters'], 2), 1, 0, 'C', $fill);
            $this->Cell(20, 5, $cuttingText, 1, 0, 'C', $fill);
            $this->Cell(40, 5, '$' . number_format($foam['unit_cost'], 2) . '/m²', 1, 0, 'R', $fill);
            $this->Cell(39, 5, '$' . number_format($foam['line_total'], 2), 1, 1, 'R', $fill);

            $fill = !$fill;
        }

        // Foam subtotal
        $this->SetFont('helvetica', 'B', 8);
        $foamTotal = array_sum(array_column($foamItems, 'line_total'));
        $this->Cell(147, 5, 'Foam Subtotal:', 1, 0, 'R');
        $this->Cell(39, 5, '$' . number_format($foamTotal, 2), 1, 1, 'R');
    }

    private function renderMiscTable($miscItems) {
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(52, 58, 64);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 6, 'Miscellaneous', 0, 1, 'L', true);

        // Table header
        $this->SetFont('helvetica', 'B', 8);
        $this->SetFillColor(233, 236, 239);
        $this->SetTextColor(0, 0, 0);

        $this->Cell(95, 5, 'Item', 1, 0, 'L', true);
        $this->Cell(22, 5, 'Qty', 1, 0, 'C', true);
        $this->Cell(30, 5, 'Unit Price', 1, 0, 'R', true);
        $this->Cell(39, 5, 'Total', 1, 1, 'R', true);

        // Table rows
        $this->SetFont('helvetica', '', 8);
        $fill = false;
        $this->SetFillColor(248, 249, 250);
        $miscTotal = 0;

        foreach ($miscItems as $misc) {
            $qty = $misc['quantity'] ?? 1;
            $lineTotal = $misc['price'] * $qty;
            $miscTotal += $lineTotal;

            $this->Cell(95, 5, $misc['name'], 1, 0, 'L', $fill);
            $this->Cell(22, 5, $qty, 1, 0, 'C', $fill);
            $this->Cell(30, 5, '$' . number_format($misc['price'], 2), 1, 0, 'R', $fill);
            $this->Cell(39, 5, '$' . number_format($lineTotal, 2), 1, 1, 'R', $fill);

            $fill = !$fill;
        }

        // Misc subtotal
        $this->SetFont('helvetica', 'B', 8);
        $this->Cell(147, 5, 'Misc Subtotal:', 1, 0, 'R');
        $this->Cell(39, 5, '$' . number_format($miscTotal, 2), 1, 1, 'R');
    }

    private function renderLabourTable($quote) {
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(52, 58, 64);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 6, 'Labour', 0, 1, 'L', true);

        // Table header
        $this->SetFont('helvetica', 'B', 8);
        $this->SetFillColor(233, 236, 239);
        $this->SetTextColor(0, 0, 0);

        $this->Cell(95, 5, 'Category', 1, 0, 'L', true);
        $this->Cell(22, 5, 'Time', 1, 0, 'C', true);
        $this->Cell(30, 5, 'Rate', 1, 0, 'R', true);
        $this->Cell(39, 5, 'Total', 1, 1, 'R', true);

        // Labour categories
        $labourCategories = [
            'labour_stripping' => 'Stripping',
            'labour_patterns' => 'Patterns',
            'labour_cutting' => 'Cutting',
            'labour_sewing' => 'Sewing',
            'labour_upholstery' => 'Upholstery',
            'labour_assembly' => 'Assembly',
            'labour_handling' => 'Handling'
        ];

        $this->SetFont('helvetica', '', 8);
        $fill = false;
        $this->SetFillColor(248, 249, 250);
        $totalMinutes = 0;
        $hourlyRate = (float)$quote['labour_rate'];

        foreach ($labourCategories as $field => $label) {
            $minutes = (int)($quote[$field] ?? 0);
            if ($minutes > 0) {
                $totalMinutes += $minutes;
                $hours = $minutes / 60;
                $cost = $hours * $hourlyRate;

                $this->Cell(95, 5, $label, 1, 0, 'L', $fill);
                $this->Cell(22, 5, $this->formatTime($minutes), 1, 0, 'C', $fill);
                $this->Cell(30, 5, '$' . number_format($hourlyRate, 2) . '/hr', 1, 0, 'R', $fill);
                $this->Cell(39, 5, '$' . number_format($cost, 2), 1, 1, 'R', $fill);

                $fill = !$fill;
            }
        }

        // Labour subtotal
        $this->SetFont('helvetica', 'B', 8);
        $totalHours = $totalMinutes / 60;
        $labourTotal = $totalHours * $hourlyRate;

        $rateType = ucfirst($quote['labour_rate_type'] ?? 'standard');
        $this->Cell(95, 5, 'Labour Subtotal (' . $rateType . '):', 1, 0, 'R');
        $this->Cell(22, 5, $this->formatTime($totalMinutes), 1, 0, 'C');
        $this->Cell(30, 5, '', 1, 0, 'R');
        $this->Cell(39, 5, '$' . number_format($labourTotal, 2), 1, 1, 'R');
    }

    private function renderTotals($quote) {
        // Move to right side for totals
        $this->SetX(110);

        // Totals box
        $this->SetFont('helvetica', '', 9);
        $this->SetFillColor(248, 249, 250);

        // Subtotal
        $this->Cell(50, 5, 'Subtotal (excl. GST):', 0, 0, 'R');
        $this->Cell(36, 5, '$' . number_format($quote['total_excl_gst'], 2), 0, 1, 'R');

        // GST
        $this->SetX(110);
        $gstRate = (float)($this->getSetting(Database::getInstance(), 'gst_rate', 15));
        $this->Cell(50, 5, 'GST (' . $gstRate . '%):', 0, 0, 'R');
        $this->Cell(36, 5, '$' . number_format($quote['gst_amount'], 2), 0, 1, 'R');

        // Total
        $this->SetX(110);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetFillColor(52, 58, 64);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(50, 7, 'TOTAL (incl. GST):', 1, 0, 'R', true);
        $this->Cell(36, 7, '$' . number_format($quote['total_incl_gst'], 2), 1, 1, 'R', true);
        $this->SetTextColor(0, 0, 0);
    }

    private function hasLabour($quote) {
        return ($quote['labour_stripping'] ?? 0) > 0 ||
               ($quote['labour_patterns'] ?? 0) > 0 ||
               ($quote['labour_cutting'] ?? 0) > 0 ||
               ($quote['labour_sewing'] ?? 0) > 0 ||
               ($quote['labour_upholstery'] ?? 0) > 0 ||
               ($quote['labour_assembly'] ?? 0) > 0 ||
               ($quote['labour_handling'] ?? 0) > 0;
    }

    private function formatTime($minutes) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return $hours . 'h ' . $mins . 'm';
        } elseif ($hours > 0) {
            return $hours . 'h';
        } else {
            return $mins . 'm';
        }
    }

    private function truncate($string, $length) {
        if (strlen($string) > $length) {
            return substr($string, 0, $length - 3) . '...';
        }
        return $string;
    }

    /**
     * Save PDF to uploads folder and update quote record
     */
    public function saveToFile($quote) {
        $filename = 'quote_' . $quote['quote_number'] . '_rev' . $quote['revision'] . '_' . date('Ymd_His') . '.pdf';
        $filepath = dirname(dirname(__DIR__)) . '/uploads/pdfs/quotes/' . $filename;

        $this->generate($quote, $filepath);

        // Update quote with PDF path
        $db = Database::getInstance();
        $db->update('quotes', ['pdf_path' => 'uploads/pdfs/quotes/' . $filename], 'id = :id', ['id' => $quote['id']]);

        return $filepath;
    }
}
