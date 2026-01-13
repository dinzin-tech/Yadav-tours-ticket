<?php
// app/libraries/PDFGenerator.php
require_once BASE_PATH . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class PDFGenerator {
    private $dompdf;
    private $agency_logo_path;
    
    public function __construct() {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isPhpEnabled', true);
        
        $this->dompdf = new Dompdf($options);
        
        // Set logo path (you can customize this)
        $this->agency_logo_path = BASE_PATH . '/public/assets/logo.png';
    }
    
    public function generateTicketPDF($ticket_data, $agency_details) {
        // Generate HTML based on ticket type
        $html = $this->generateTicketHTML($ticket_data, $agency_details);
        
        // Load HTML and render PDF
        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();
        
        return $this->dompdf->output();
    }
    
    private function generateTicketHTML($ticket, $agency) {
        $type = $ticket['ticket_type'];
        
        // Use different template for each ticket type
        switch ($type) {
            case 'train':
                return $this->trainTicketHTML($ticket, $agency);
            case 'flight':
                return $this->flightTicketHTML($ticket, $agency);
            case 'bus':
                return $this->busTicketHTML($ticket, $agency);
            case 'cab':
                return $this->cabTicketHTML($ticket, $agency);
            case 'tour':
                return $this->tourTicketHTML($ticket, $agency);
            default:
                return $this->genericTicketHTML($ticket, $agency);
        }
    }
    
    private function trainTicketHTML($ticket, $agency) {
        $ticket_data = $ticket['ticket_data'];
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Train Ticket - ' . $ticket['pnr'] . '</title>
            <style>
                ' . $this->getCommonCSS() . '
                .train-header {
                    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 10px 10px 0 0;
                }
                .train-details {
                    background: #f8f9fa;
                    border: 2px dashed #1e3c72;
                    padding: 15px;
                    margin: 10px 0;
                }
                .coach-seat {
                    background: #1e3c72;
                    color: white;
                    padding: 5px 15px;
                    border-radius: 20px;
                    display: inline-block;
                }
            </style>
        </head>
        <body>
            <div class="ticket-container">
                ' . $this->getHeaderHTML($agency, $ticket) . '
                
                <div class="train-header">
                    <h2 style="margin: 0; text-align: center;">INDIAN RAILWAYS E-TICKET</h2>
                    <p style="text-align: center; margin: 5px 0;">Booking Status: CONFIRMED</p>
                </div>
                
                <div class="section">
                    <h3>Journey Details</h3>
                    <div class="train-details">
                        <table width="100%">
                            <tr>
                                <td width="30%"><strong>Train No:</strong> ' . ($ticket_data['train_number'] ?? 'N/A') . '</td>
                                <td width="40%"><strong>Train Name:</strong> ' . ($ticket_data['train_name'] ?? 'N/A') . '</td>
                                <td width="30%"><strong>Class:</strong> ' . ($ticket_data['class'] ?? 'N/A') . '</td>
                            </tr>
                            <tr>
                                <td><strong>From:</strong> ' . ($ticket_data['from_station'] ?? 'N/A') . '</td>
                                <td><strong>To:</strong> ' . ($ticket_data['to_station'] ?? 'N/A') . '</td>
                                <td><strong>PNR:</strong> ' . $ticket['pnr'] . '</td>
                            </tr>
                            <tr>
                                <td><strong>Departure:</strong> ' . date('d/m/Y H:i', strtotime($ticket_data['departure_datetime'] ?? '')) . '</td>
                                <td><strong>Arrival:</strong> ' . date('d/m/Y H:i', strtotime($ticket_data['arrival_datetime'] ?? '')) . '</td>
                                <td><strong>Date of Journey:</strong> ' . date('d/m/Y', strtotime($ticket['journey_date'])) . '</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                ' . $this->getPassengersHTML($ticket_data) . '
                
                <div class="section">
                    <h3>Coach & Seat Details</h3>
                    <div class="row">
                        <div class="col">
                            <span class="coach-seat">
                                Coach: ' . ($ticket_data['coach'] ?? 'N/A') . ' | Seat: ' . ($ticket_data['seat'] ?? 'N/A') . '
                            </span>
                        </div>
                    </div>
                </div>
                
                ' . $this->getFareHTML($ticket) . '
                ' . $this->getTermsHTML($agency) . '
                ' . $this->getBarcodeHTML($ticket) . '
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    private function flightTicketHTML($ticket, $agency) {
        $ticket_data = $ticket['ticket_data'];
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Flight Ticket - ' . $ticket['pnr'] . '</title>
            <style>
                ' . $this->getCommonCSS() . '
                .flight-header {
                    background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 10px 10px 0 0;
                }
                .flight-segment {
                    background: #e3f2fd;
                    border-left: 5px solid #0d47a1;
                    padding: 15px;
                    margin: 10px 0;
                }
                .boarding-pass {
                    border: 2px solid #0d47a1;
                    padding: 15px;
                    margin: 15px 0;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="ticket-container">
                ' . $this->getHeaderHTML($agency, $ticket) . '
                
                <div class="flight-header">
                    <h2 style="margin: 0; text-align: center;">E-TICKET / ELECTRONIC TICKET RECEIPT</h2>
                    <p style="text-align: center; margin: 5px 0;">Airline: ' . ($ticket_data['airline'] ?? 'N/A') . '</p>
                </div>
                
                <div class="section">
                    <h3>Flight Itinerary</h3>
                    <div class="flight-segment">
                        <table width="100%">
                            <tr>
                                <td width="40%">
                                    <strong>From:</strong> ' . ($ticket_data['from_airport'] ?? 'N/A') . '<br>
                                    ' . date('d/m/Y H:i', strtotime($ticket_data['departure_datetime'] ?? '')) . '
                                </td>
                                <td width="20%" style="text-align: center;">
                                    <div style="color: #0d47a1; font-weight: bold;">➔</div>
                                    <div>' . ($ticket_data['flight_number'] ?? 'N/A') . '</div>
                                    <div style="font-size: 12px;">' . ($ticket_data['class'] ?? 'Economy') . '</div>
                                </td>
                                <td width="40%">
                                    <strong>To:</strong> ' . ($ticket_data['to_airport'] ?? 'N/A') . '<br>
                                    ' . date('d/m/Y H:i', strtotime($ticket_data['arrival_datetime'] ?? '')) . '
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                ' . $this->getPassengersHTML($ticket_data) . '
                
                <div class="section">
                    <h3>Boarding Information</h3>
                    <div class="boarding-pass">
                        <h4>BOARDING PASS</h4>
                        <p>Gate: ' . ($ticket_data['gate'] ?? 'TBA') . ' | Terminal: ' . ($ticket_data['terminal'] ?? 'TBA') . '</p>
                        <p>Seat: ' . ($ticket_data['seat'] ?? 'TBA') . ' | Class: ' . ($ticket_data['class'] ?? 'Economy') . '</p>
                    </div>
                </div>
                
                ' . $this->getFareHTML($ticket) . '
                ' . $this->getTermsHTML($agency) . '
                ' . $this->getBarcodeHTML($ticket) . '
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    private function getCommonCSS() {
        return '
            @page { margin: 0; }
            body { 
                font-family: DejaVu Sans, Arial, sans-serif; 
                font-size: 12px; 
                margin: 20px; 
                color: #333;
            }
            .ticket-container {
                border: 2px solid #ddd;
                border-radius: 10px;
                padding: 0;
                max-width: 800px;
                margin: 0 auto;
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 3px solid #fff;
            }
            .agency-logo {
                max-height: 60px;
            }
            .agency-info {
                text-align: right;
            }
            .section {
                padding: 15px;
                border-bottom: 1px dashed #ddd;
            }
            .section:last-child {
                border-bottom: none;
            }
            .section h3 {
                color: #667eea;
                margin-top: 0;
                border-bottom: 1px solid #eee;
                padding-bottom: 5px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th {
                background: #f5f5f5;
                padding: 8px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            td {
                padding: 8px;
                border-bottom: 1px solid #eee;
            }
            .passenger-table {
                margin: 10px 0;
            }
            .barcode {
                text-align: center;
                padding: 15px;
                border-top: 2px solid #667eea;
                margin-top: 20px;
            }
            .terms {
                font-size: 10px;
                color: #666;
                line-height: 1.4;
            }
            .row {
                display: flex;
                margin-bottom: 10px;
            }
            .col {
                flex: 1;
                padding: 0 10px;
            }
            .highlight {
                background: #f8f9fa;
                padding: 10px;
                border-radius: 5px;
                margin: 5px 0;
            }
        ';
    }
    
    private function getHeaderHTML($agency, $ticket) {
        $logo_html = '';
        if (file_exists($this->agency_logo_path)) {
            $logo_html = '<img src="' . $this->agency_logo_path . '" class="agency-logo" alt="Agency Logo">';
        }
        
        return '
        <div class="header">
            <div>
                ' . $logo_html . '
            </div>
            <div class="agency-info">
                <h2 style="margin: 0;">' . ($agency['name'] ?? 'Travel Agency') . '</h2>
                <p style="margin: 5px 0 0 0;">
                    ' . ($agency['address'] ?? '') . '<br>
                    Phone: ' . ($agency['phone'] ?? '') . ' | 
                    Email: ' . ($agency['email'] ?? '') . '
                </p>
            </div>
        </div>';
    }
    
    private function getPassengersHTML($ticket_data) {
        if (empty($ticket_data['passengers'])) {
            return '';
        }
        
        $html = '<div class="section">
            <h3>Passenger Details</h3>
            <table class="passenger-table">
                <tr>
                    <th>S.No</th>
                    <th>Passenger Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>ID Proof</th>
                    <th>Seat</th>
                </tr>';
        
        foreach ($ticket_data['passengers'] as $index => $passenger) {
            $html .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td>' . ($passenger['name'] ?? '') . '</td>
                    <td>' . ($passenger['age'] ?? '') . '</td>
                    <td>' . ($passenger['gender'] ?? '') . '</td>
                    <td>' . ($passenger['id_type'] ?? '') . ': ' . ($passenger['id_number'] ?? '') . '</td>
                    <td>' . ($passenger['seat'] ?? '') . '</td>
                </tr>';
        }
        
        $html .= '</table></div>';
        return $html;
    }
    
    private function getFareHTML($ticket) {
        $ticket_data = $ticket['ticket_data'];
        
        return '<div class="section">
            <h3>Fare Details</h3>
            <table width="50%" style="margin-left: auto;">
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
                <tr style="border-top: 2px solid #667eea; font-weight: bold;">
                    <td>Total Amount:</td>
                    <td align="right">₹' . number_format($ticket['total_amount'], 2) . '</td>
                </tr>
            </table>
        </div>';
    }
    
    private function getTermsHTML($agency) {
        return '<div class="section terms">
            <h3>Terms & Conditions</h3>
            <ol>
                <li>This is a computer generated ticket, no signature required.</li>
                <li>Please carry valid ID proof during journey.</li>
                <li>Reporting time: 30 minutes before departure for trains/buses, 2 hours for flights.</li>
                <li>Cancellation and refund as per service provider rules.</li>
                <li>For any queries, contact: ' . ($agency['phone'] ?? '') . ' or ' . ($agency['email'] ?? '') . '</li>
                <li>Ticket issued by: ' . ($agency['name'] ?? 'Travel Agency') . '</li>
            </ol>
            <p style="text-align: center; margin-top: 15px; font-style: italic;">
                Thank you for choosing our services!
            </p>
        </div>';
    }
    
    private function getBarcodeHTML($ticket) {
        return '<div class="barcode">
            <p style="font-family: monospace; font-size: 14px; letter-spacing: 3px; margin: 0;">
                |||||| ' . $ticket['ticket_id'] . ' |||||| ' . $ticket['pnr'] . ' ||||||
            </p>
            <p style="margin: 5px 0; font-size: 11px;">
                Ticket ID: ' . $ticket['ticket_id'] . ' | PNR: ' . $ticket['pnr'] . '
            </p>
            <p style="font-size: 10px; color: #666;">
                Generated on: ' . date('d/m/Y H:i:s') . '
            </p>
        </div>';
    }
}
?>