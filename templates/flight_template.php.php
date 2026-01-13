<!-- templates/flight_template.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Flight Ticket - <?= htmlspecialchars($data['pnr']) ?></title>
    <style>
        @page { margin: 0; }
        body { 
            font-family: DejaVu Sans, Arial, sans-serif; 
            font-size: 11px; 
            margin: 20px; 
        }
        .ticket {
            border: 2px solid #1e88e5;
            border-radius: 10px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e88e5 0%, #0d47a1 100%);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .airline-logo {
            height: 40px;
        }
        .pnr-box {
            background: white;
            color: #1e88e5;
            padding: 5px 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        .section {
            padding: 15px;
            border-bottom: 1px dashed #ddd;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section-title {
            color: #1e88e5;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .flight-segment {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            background: #f5f9ff;
            border-radius: 5px;
        }
        .airport-code {
            font-size: 18px;
            font-weight: bold;
            color: #1e88e5;
        }
        .flight-info {
            text-align: center;
        }
        .barcode {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .terms {
            font-size: 9px;
            color: #666;
            line-height: 1.3;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th {
            background: #f5f5f5;
            padding: 8px;
            text-align: left;
        }
        .table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .boarding-pass {
            border-left: 5px solid #1e88e5;
            padding-left: 10px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <!-- Header -->
        <div class="header">
            <div>
                <h2 style="margin: 0;">E-TICKET / ELECTRONIC TICKET RECEIPT</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">
                    <?= htmlspecialchars($data['airline_name'] ?? 'Airline Name') ?>
                </p>
            </div>
            <div class="pnr-box">
                PNR: <?= htmlspecialchars($data['pnr']) ?>
            </div>
        </div>
        
        <!-- Passenger Info -->
        <div class="section">
            <div class="section-title">PASSENGER INFORMATION</div>
            <table class="table">
                <tr>
                    <th>Passenger Name</th>
                    <th>Ticket Number</th>
                    <th>Frequent Flyer</th>
                    <th>Contact</th>
                </tr>
                <?php foreach ($data['passengers'] as $index => $passenger): ?>
                <tr>
                    <td><?= htmlspecialchars($passenger['name']) ?></td>
                    <td><?= htmlspecialchars($data['ticket_number'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($passenger['frequent_flyer'] ?? 'N/A') ?></td>
                    <td>
                        <?= htmlspecialchars($passenger['email'] ?? '') ?><br>
                        <?= htmlspecialchars($passenger['phone'] ?? '') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <!-- Flight Itinerary -->
        <div class="section">
            <div class="section-title">FLIGHT ITINERARY</div>
            <?php foreach ($data['segments'] as $segment): ?>
            <div class="flight-segment">
                <div class="departure">
                    <div class="airport-code"><?= htmlspecialchars($segment['departure_code']) ?></div>
                    <div><?= htmlspecialchars($segment['departure_city']) ?></div>
                    <div><strong><?= date('d M Y', strtotime($segment['departure_datetime'])) ?></strong></div>
                    <div><?= date('H:i', strtotime($segment['departure_datetime'])) ?></div>
                </div>
                
                <div class="flight-info">
                    <div style="color: #1e88e5; font-weight: bold;">
                        <?= htmlspecialchars($segment['flight_number']) ?>
                    </div>
                    <div style="font-size: 10px; color: #666;">
                        <?= htmlspecialchars($segment['aircraft_type'] ?? 'Aircraft') ?>
                    </div>
                    <div style="margin: 5px 0;">
                        <i class="fas fa-plane" style="color: #1e88e5;"></i>
                        <div style="font-size: 9px; color: #666;">
                            Duration: <?= htmlspecialchars($segment['duration']) ?>
                        </div>
                    </div>
                    <div style="font-size: 10px; color: #1e88e5; font-weight: bold;">
                        <?= htmlspecialchars($segment['class']) ?>
                    </div>
                </div>
                
                <div class="arrival">
                    <div class="airport-code"><?= htmlspecialchars($segment['arrival_code']) ?></div>
                    <div><?= htmlspecialchars($segment['arrival_city']) ?></div>
                    <div><strong><?= date('d M Y', strtotime($segment['arrival_datetime'])) ?></strong></div>
                    <div><?= date('H:i', strtotime($segment['arrival_datetime'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Fare Details -->
        <div class="section">
            <div class="section-title">FARE DETAILS</div>
            <table width="50%" style="margin-left: auto;">
                <tr>
                    <td>Base Fare:</td>
                    <td align="right">$<?= number_format($data['base_fare'], 2) ?></td>
                </tr>
                <tr>
                    <td>Taxes & Fees:</td>
                    <td align="right">$<?= number_format($data['taxes'], 2) ?></td>
                </tr>
                <tr>
                    <td>Service Charge:</td>
                    <td align="right">$<?= number_format($data['service_charge'], 2) ?></td>
                </tr>
                <tr style="border-top: 2px solid #1e88e5; font-weight: bold;">
                    <td>Total Amount:</td>
                    <td align="right">$<?= number_format($data['total_amount'], 2) ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Important Information -->
        <div class="section">
            <div class="section-title">IMPORTANT INFORMATION</div>
            <div class="boarding-pass">
                <p><strong>CHECK-IN:</strong> Available 48 hours to 2 hours before departure</p>
                <p><strong>BAGGAGE:</strong> Cabin: <?= $data['baggage_cabin'] ?? '7kg' ?>, 
                Check-in: <?= $data['baggage_checkin'] ?? '20kg' ?></p>
                <p><strong>DOCUMENTS:</strong> Passport and visa required for international flights</p>
            </div>
        </div>
        
        <!-- Barcode -->
        <div class="barcode">
            <p style="margin: 0 0 10px 0; font-weight: bold;">BOARDING PASS</p>
            <p style="margin: 0; font-family: monospace; font-size: 14px; letter-spacing: 3px;">
                <?= str_repeat('|', 40) ?>
            </p>
            <p style="margin: 5px 0; font-size: 10px; color: #666;">
                PNR: <?= htmlspecialchars($data['pnr']) ?> | 
                Ticket ID: <?= $data['ticket_id'] ?>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="section">
            <div class="terms">
                <p><strong>TERMS & CONDITIONS:</strong></p>
                <p>1. This is an electronic ticket receipt. No physical ticket will be issued.</p>
                <p>2. Changes and cancellations subject to airline rules and fees.</p>
                <p>3. Passenger must present valid photo ID at check-in and boarding.</p>
                <p>4. Issued by: <?= htmlspecialchars($data['agency_name']) ?> | 
                   Contact: <?= htmlspecialchars($data['agency_phone']) ?> | 
                   Email: <?= htmlspecialchars($data['agency_email']) ?></p>
                <p>5. Ticket issued on: <?= date('d/m/Y H:i:s') ?></p>
            </div>
        </div>
    </div>
</body>
</html>