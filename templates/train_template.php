<!-- templates/train_template.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Train Ticket - <?= htmlspecialchars($data['pnr']) ?></title>
    <style>
        /* CSS optimized for Dompdf */
        @page { margin: 20px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .ticket-container { border: 2px solid #000; padding: 20px; }
        .header { border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
        .logo { height: 60px; float: left; }
        .agency-info { float: right; text-align: right; }
        .clear { clear: both; }
        .section { margin-bottom: 20px; border-bottom: 1px dashed #ccc; padding-bottom: 15px; }
        .section-title { background: #f5f5f5; padding: 5px 10px; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .fare-breakdown { width: 50%; margin-left: auto; }
        .barcode { text-align: center; margin-top: 20px; }
        .terms { font-size: 10px; color: #666; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="ticket-container">
        <!-- Header with Logo -->
        <div class="header">
            <img src="logo.png" class="logo" alt="Company Logo">
            <div class="agency-info">
                <h2><?= htmlspecialchars($data['agency_name']) ?></h2>
                <p>Email: <?= htmlspecialchars($data['agency_email']) ?></p>
                <p>Phone: <?= htmlspecialchars($data['agency_phone']) ?></p>
                <p>Booking Date: <?= date('d/m/Y', strtotime($data['booking_date'])) ?></p>
            </div>
            <div class="clear"></div>
        </div>
        
        <!-- PNR and Journey Details -->
        <div class="section">
            <div class="section-title">Booking & Journey Details</div>
            <table width="100%">
                <tr>
                    <td width="25%"><strong>PNR:</strong> <?= htmlspecialchars($data['pnr']) ?></td>
                    <td width="25%"><strong>Train No:</strong> <?= htmlspecialchars($data['train_number']) ?></td>
                    <td width="25%"><strong>Train Name:</strong> <?= htmlspecialchars($data['train_name']) ?></td>
                    <td width="25%"><strong>Class:</strong> <?= htmlspecialchars($data['class']) ?></td>
                </tr>
                <tr>
                    <td><strong>From:</strong> <?= htmlspecialchars($data['departure_station']) ?></td>
                    <td><strong>To:</strong> <?= htmlspecialchars($data['arrival_station']) ?></td>
                    <td><strong>Departure:</strong> <?= date('d/m/Y H:i', strtotime($data['departure_datetime'])) ?></td>
                    <td><strong>Arrival:</strong> <?= date('d/m/Y H:i', strtotime($data['arrival_datetime'])) ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Passenger Details -->
        <div class="section">
            <div class="section-title">Passenger Information</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Passenger Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Coach</th>
                        <th>Seat/Berth</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['passengers'] as $index => $passenger): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($passenger['name']) ?></td>
                        <td><?= htmlspecialchars($passenger['age']) ?></td>
                        <td><?= htmlspecialchars($passenger['gender']) ?></td>
                        <td><?= htmlspecialchars($data['coach_number']) ?></td>
                        <td><?= htmlspecialchars($passenger['seat']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Fare Details -->
        <div class="section">
            <div class="section-title">Fare Breakdown</div>
            <table class="fare-breakdown">
                <tr>
                    <td>Base Fare:</td>
                    <td align="right">₹<?= number_format($data['base_fare'], 2) ?></td>
                </tr>
                <tr>
                    <td>Taxes & Charges:</td>
                    <td align="right">₹<?= number_format($data['taxes'], 2) ?></td>
                </tr>
                <tr>
                    <td>Service Charge:</td>
                    <td align="right">₹<?= number_format($data['service_charge'], 2) ?></td>
                </tr>
                <tr style="border-top: 2px solid #000; font-weight: bold;">
                    <td>Total Amount:</td>
                    <td align="right">₹<?= number_format($data['total_amount'], 2) ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Barcode and Terms -->
        <div class="barcode">
            <p>Ticket ID: <?= $ticketId ?></p>
            <!-- Barcode image would be generated here -->
        </div>
        
        <div class="terms">
            <p><strong>Terms & Conditions:</strong></p>
            <p>1. This is a computer generated ticket, no signature required.</p>
            <p>2. Please carry valid ID proof during journey.</p>
            <p>3. Boarding point: <?= htmlspecialchars($data['boarding_point']) ?></p>
            <p>4. For any queries, contact: <?= htmlspecialchars($data['agency_phone']) ?></p>
        </div>
    </div>
</body>
</html>