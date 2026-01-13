<?php
// app/libraries/TicketTemplates.php

class TicketTemplates {
    
    public static function generateHTML($ticket, $agency) {
        $type = $ticket['ticket_type'];
        
        switch ($type) {
            case 'train':
                return self::trainTemplate($ticket, $agency);
            case 'flight':
                return self::flightTemplate($ticket, $agency);
            case 'bus':
                return self::busTemplate($ticket, $agency);
            case 'cab':
                return self::cabTemplate($ticket, $agency);
            case 'tour':
                return self::tourTemplate($ticket, $agency);
            default:
                return self::genericTemplate($ticket, $agency);
        }
    }
    
    private static function trainTemplate($ticket, $agency) {
        $ticket_data = $ticket['ticket_data'];
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Train Ticket - ' . $ticket['ticket_id'] . '</title>
            <style>
                ' . self::getCommonCSS($agency) . '
                .train-header {
                    background: ' . ($agency['brand_color'] ?? '#1e3c72') . ';
                    color: white;
                    padding: 15px;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="ticket-container">
                ' . self::getHeader($agency) . '
                
                <div class="train-header">
                    <h2 style="margin: 0;">TRAIN TICKET</h2>
                    <p style="margin: 5px 0;">' . ($agency['slogan'] ?? 'Safe Journey, Happy Memories') . '</p>
                </div>
                
                <div class="section">
                    <h3>Journey Details</h3>
                    <table width="100%">
                        <tr>
                            <td width="30%"><strong>Train:</strong></td>
                            <td width="70%">' . ($ticket_data['train_number'] ?? '') . ' - ' . ($ticket_data['train_name'] ?? '') . '</td>
                        </tr>
                        <tr>
                            <td><strong>From:</strong></td>
                            <td>' . ($ticket_data['from_station'] ?? '') . '</td>
                        </tr>
                        <tr>
                            <td><strong>To:</strong></td>
                            <td>' . ($ticket_data['to_station'] ?? '') . '</td>
                        </tr>
                        <tr>
                            <td><strong>Date:</strong></td>
                            <td>' . date('d/m/Y', strtotime($ticket_data['departure_datetime'] ?? '')) . '</td>
                        </tr>
                        <tr>
                            <td><strong>Time:</strong></td>
                            <td>' . date('H:i', strtotime($ticket_data['departure_datetime'] ?? '')) . '</td>
                        </tr>
                        <tr>
                            <td><strong>Class:</strong></td>
                            <td>' . ($ticket_data['class'] ?? '') . '</td>
                        </tr>
                        <tr>
                            <td><strong>Coach/Seat:</strong></td>
                            <td>' . ($ticket_data['coach'] ?? '') . ' / ' . ($ticket_data['seat'] ?? '') . '</td>
                        </tr>
                    </table>
                </div>
                
                ' . self::getPassengersSection($ticket_data) . '
                ' . self::getFareSection($ticket) . '
                ' . self::getTermsSection($agency) . '
                ' . self::getBarcode($ticket) . '
            </div>
        </body>
        </html>';
    }
    
    private static function getCommonCSS($agency) {
        $primary_color = $agency['brand_color'] ?? '#1e3c72';
        $secondary_color = $agency['secondary_color'] ?? '#2a5298';
        
        return '
        @page { margin: 0; }
        body { 
            font-family: DejaVu Sans, Arial, sans-serif; 
            font-size: 11px; 
            margin: 20px; 
            color: #333;
        }
        .ticket-container {
            border: 3px solid ' . $primary_color . ';
            border-radius: 10px;
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, ' . $primary_color . ' 0%, ' . $secondary_color . ' 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .agency-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .agency-contact {
            font-size: 11px;
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .section {
            padding: 15px;
            border-bottom: 1px dashed #ddd;
        }
        .section h3 {
            color: ' . $primary_color . ';
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 5px 0;
            vertical-align: top;
        }
        .barcode {
            text-align: center;
            padding: 15px;
            border-top: 2px solid ' . $primary_color . ';
        }
        .terms {
            font-size: 9px;
            color: #666;
            line-height: 1.3;
        }
        ';
    }
    
    private static function getHeader($agency) {
        $logo_html = '';
        if (!empty($agency['logo_path']) && file_exists($agency['logo_path'])) {
            $logo_html = '<img src="' . $agency['logo_path'] . '" style="max-height: 60px; margin-bottom: 10px;">';
        }
        
        return '
        <div class="header">
            ' . $logo_html . '
            <h1 class="agency-name">' . ($agency['agency_name'] ?? 'Your Travel Agency') . '</h1>
            <p class="agency-contact">
                ' . ($agency['agency_address'] ?? '') . '<br>
                Phone: ' . ($agency['agency_phone'] ?? '') . ' | 
                Email: ' . ($agency['agency_email'] ?? '') . ' | 
                Website: ' . ($agency['website'] ?? 'www.yourcompany.com') . '
            </p>
        </div>';
    }
    
    private static function getPassengersSection($ticket_data) {
        if (empty($ticket_data['passengers'])) {
            return '';
        }
        
        $html = '<div class="section">
            <h3>Passenger Details</h3>
            <table border="1" cellpadding="5">
                <tr>
                    <th>S.No</th>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Seat</th>
                </tr>';
        
        foreach ($ticket_data['passengers'] as $index => $passenger) {
            $html .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td>' . ($passenger['name'] ?? '') . '</td>
                    <td>' . ($passenger['age'] ?? '') . '</td>
                    <td>' . ($passenger['gender'] ?? '') . '</td>
                    <td>' . ($passenger['seat'] ?? '') . '</td>
                </tr>';
        }
        
        $html .= '</table></div>';
        return $html;
    }
    
    private static function getFareSection($ticket) {
        $ticket_data = $ticket['ticket_data'] ?? [];
        
        return '<div class="section">
            <h3>Fare Details</h3>
            <table width="60%" style="margin-left: auto;">
                <tr>
                    <td>Base Fare:</td>
                    <td align="right">₹' . number_format($ticket_data['base_fare'] ?? 0, 2) . '</td>
                </tr>
                <tr>
                    <td>Taxes & Charges:</td>
                    <td align="right">₹' . number_format($ticket_data['tax_amount'] ?? 0, 2) . '</td>
                </tr>
                <tr>
                    <td>Service Charge:</td>
                    <td align="right">₹' . number_format($ticket_data['service_charge'] ?? 0, 2) . '</td>
                </tr>
                <tr style="border-top: 2px solid #000; font-weight: bold;">
                    <td>Total Amount:</td>
                    <td align="right">₹' . number_format($ticket['total_amount'], 2) . '</td>
                </tr>
            </table>
        </div>';
    }
    
    private static function getTermsSection($agency) {
        return '<div class="section terms">
            <h3>Terms & Conditions</h3>
            <ol>
                <li>This is a computer generated ticket, no signature required.</li>
                <li>Please carry valid ID proof during journey.</li>
                <li>Reporting time: 30 minutes before departure.</li>
                <li>Cancellation as per service provider rules.</li>
                <li>For assistance, contact: ' . ($agency['agency_phone'] ?? '') . '</li>
                <li>Issued by: ' . ($agency['agency_name'] ?? 'Travel Agency') . '</li>
            </ol>
            <p style="text-align: center; font-style: italic; margin-top: 10px;">
                Thank you for choosing our services!
            </p>
        </div>';
    }
    
    private static function getBarcode($ticket) {
        return '<div class="barcode">
            <p style="font-family: monospace; font-size: 14px; letter-spacing: 3px; margin: 0;">
                |||||| ' . $ticket['ticket_id'] . ' |||||| ' . $ticket['pnr'] . ' ||||||
            </p>
            <p style="margin: 5px 0; font-size: 10px;">
                Ticket ID: ' . $ticket['ticket_id'] . ' | PNR: ' . $ticket['pnr'] . '
            </p>
            <p style="font-size: 9px; color: #666;">
                Generated on: ' . date('d/m/Y H:i:s') . '
            </p>
        </div>';
    }
}
?>