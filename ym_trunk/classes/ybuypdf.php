<?php

function ybuy_do_pdf($id) {
	global $wpdb, $ybuy_country_list;

	$query = 'SELECT * FROM ' . $wpdb->prefix . 'ybuy_purchase yp
LEFT JOIN ' . $wpdb->prefix . 'ybuy_purchase_download_assoc pda ON yp.id = pda.purchase_id
LEFT JOIN ' . $wpdb->prefix . 'ybuy_purchase_product_assoc ppa ON ppa.purchase_id = yp.id
LEFT JOIN ' . $wpdb->prefix . 'ybuy_product prod ON prod.id = ppa.product_id
LEFT JOIN ' . $wpdb->prefix . 'ybuy_purchase_attribute attr ON attr.purchase_id = yp.id
WHERE yp.id = ' . $id . ' AND attr.attribute_id = 2';
	$rows = $wpdb->get_results($query);
	$row = $rows[0];
	if ($row->current_status_id != 2) { 
		// payment not complete
		return FALSE;
	}
	$user_data = get_userdata($row->user_id);

include(YBUY_INCLUDES_DIR . 'fpdf/fpdf.php');

$pdf = new FPDF();
$pdf->AddPage();

$font = 10;

$pdf->SetFont('Arial', '', $font);
$pdf->SetFont('Arial', 'B', $font);

$cell = 45;
$cell_1 = 10;
$cell_2 = 50;
$cell_3 = 150;

$line_h = ($font / 3) + 1;

$pdf->Image('http://www.codingfutures.co.uk/wp-content/themes/codingfutures/images/logo-small.jpg', 10, 10, 60, 40);
$pdf->Cell($cell, $line_h);
$pdf->ln();

$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell(1, $line_h, 'Coding Futures');
$pdf->SetFont('Arial', '', $font);

$pdf->ln();
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell(1, $line_h, '91 Kirkstall Road');

$pdf->ln();
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell(1, $line_h, 'The Tannery');

$pdf->ln();
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell(1, $line_h, 'Leeds');

$pdf->ln();
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell(1, $line_h, 'LS3 1HS');

$pdf->ln();
$pdf->ln();
$pdf->ln();
$pdf->ln();
$pdf->ln();
$pdf->ln();
$pdf->SetFont('Arial', 'b', $font);
$pdf->Cell(1, $line_h, $user_data->ybuy_company);

$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell(1, $line_h, 'Invoice: ybuy_' . $id);

$pdf->SetFont('Arial', '', $font);
$pdf->ln();
$pdf->Cell(1, $line_h, $user_data->ybuy_address_1);
$pdf->ln();
$pdf->Cell(1, $line_h, $user_data->ybuy_address_2);

$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell(1, $line_h, date('d/m/Y', time()));

$pdf->ln();
$pdf->Cell(1, $line_h, $user_data->ybuy_city);
$pdf->ln();

$pdf->Cell(1, $line_h, $user_data->ybuy_region);

$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell(1, $line_h, 'Payment Made On: ' . date('d/m/Y', time()));

$pdf->ln();
$pdf->Cell(1, $line_h, $ybuy_country_list[$user_data->ybuy_country]);
$pdf->ln();
$pdf->Cell(1, $line_h, $user_data->ybuy_post_code);

$pdf->ln();
$pdf->ln();
$pdf->ln();
$pdf->ln();
$pdf->ln();
$pdf->ln();
$pdf->ln();
$pdf->ln();

$char = 163;
$cur = 'GBP';
$vat = 20;

if ($row->currency_id == 2) {
	$char = 36;
	$cur = 'USD';
	$vat = 0;
}

$pdf->SetFont('Arial', 'b', $font);
$pdf->SetTextColor(255);
$pdf->Cell(20, $line_h, 'Quantity', 0, 0, 'C', true);
$pdf->Cell(80, $line_h, 'Details', 0, 0, 'C', true);
$pdf->Cell(40, $line_h, 'Unit Price ' . chr($char), 0, 0, 'C', true);
$pdf->Cell(20, $line_h, 'VAT', 0, 0, 'C', true);
$pdf->Cell(30, $line_h, 'Net Subtotal ' . chr($char), 0, 0, 'C', true);
$pdf->ln();

$pdf->SetFont('Arial', '', $font);
$pdf->SetTextColor(0);

$row->price = $row->price / 100;

$pdf->Cell(20, $line_h, '1', 0, 0, 'C');
$pdf->Cell(80, $line_h, $row->name, 0, 0, 'C');
$pdf->Cell(40, $line_h, number_format($row->price, 2), 0, 0, 'C');
if ($vat) {
	$pdf->Cell(20, $line_h, $vat . '%', 0, 0, 'C');
} else {
	$pdf->Cell(20, $line_h, 'N/A', 0, 0, 'C');
}

$pdf->Cell(30, $line_h, number_format($row->price, 2), 0, 0, 'C');

$pdf->ln();
$pdf->Cell(140);
$pdf->Cell(20, $line_h, 'Net Total', 0, 0, 'R');
$pdf->Cell(30, $line_h, number_format($row->price, 2), 0, 0, 'C');
$pdf->ln();
$pdf->Cell(140);
$pdf->Cell(20, $line_h, 'VAT', 0, 0, 'R');

$add = $row->price * ($vat / 100);
$vat = $vat + 100;
$total = $row->price * ($vat / 100);

if ($add == $total) {
	$pdf->Cell(30, $line_h, 'N/A', 0, 0, 'C');
} else {
	$pdf->Cell(30, $line_h, number_format($add, 2), 0, 0, 'C');
}
$pdf->ln();
$pdf->Cell(140);
$pdf->Cell(20, $line_h, $cur . ' Total', 0, 0, 'R');
$pdf->Cell(30, $line_h, number_format($total, 2), 0, 0, 'C');

$pdf->ln();
$pdf->ln();
$pdf->ln();
$pdf->ln();

$pdf->SetFont('Arial', 'b', $font);
$pdf->Cell(1, $line_h, 'Payment Details');
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell('60');
$pdf->Cell(1, $line_h, 'Other Information', 0, 9, 'R');
$pdf->SetFont('Arial', '', $font);
$pdf->ln();
$pdf->Cell(1, $line_h, 'Paid with thanks');
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell('60');
$pdf->Cell(1, $line_h, 'Company Registration Number: 07350515', 0, 0, 'R');
$pdf->ln();

$pdf->Cell(1, $line_h, 'PayPal Transaction ID' . $row->value);
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell($cell);
$pdf->Cell('60');
$pdf->Cell(1, $line_h, 'VAT Registration Number: 107 6226 45', 0, 0, 'R');

$pdf->Output(YBUY_PLUGIN_DIR_PATH . '../../uploads/pdf/ybuy_' . $id . '.pdf');
//echo '<iframe src="/wp-content/uploads/pdf/ybuy_' . $id . '.pdf" style="width: 1000px; height: 800px"></iframe>';

//$r = wp_mail('barry@barrycarlyon.co.uk', 'test', 'test', '', YBUY_PLUGIN_DIR_PATH . '../../uploads/pdf/ybuy_' . $id . '.pdf');
//print_r($r);
	return YBUY_PLUGIN_DIR_PATH . '../../uploads/pdf/ybuy_' . $id . '.pdf';
}
/*
// no worky

include('./fpdf/html2fpdf/fpdf.php');
include('./fpdf/html2fpdf/html2fpdf.php');

ob_start();
$contents = file_get_contents('pdf.txt');
echo $contents;

//$pdf = new FPDF;
$pdf = new HTML2FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',15);
//$pdf->Write(5, ob_get_contents());
$pdf->WriteHTML(ob_get_contents());
ob_end_clean();

$pdf->Output('/var/tmp/test.pdf');
exit;
*/

