<?php
// app/controllers/TicketController.php

class TicketController {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function saveTicket($data) {
        try {
            // Check if user_id exists in session or data
            $user_id = $_SESSION['user_id'] ?? $data['user_id'] ?? 1;

            // Verify user exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? AND is_active = 1");
            $stmt->execute([$user_id]);

            if (!$stmt->fetch()) {
                // User doesn't exist, get first active user
                $stmt = $this->db->query("SELECT id FROM users WHERE is_active = 1 LIMIT 1");
                $user = $stmt->fetch();

                if (!$user) {
                    return [
                        'success' => false,
                        'message' => 'No active users found in database'
                    ];
                }
                $user_id = $user['id'];
            }

            // Generate ticket ID
            $ticket_id = $this->generateTicketId($data['ticket_type']);

            // Prepare ticket data
            $ticket_data = $this->prepareTicketData($data);

            // Convert to JSON string
            $ticket_data_json = json_encode($ticket_data, JSON_UNESCAPED_UNICODE);

            // Insert into database
            $stmt = $this->db->prepare("
                INSERT INTO tickets (
                    ticket_id, ticket_type, pnr, booking_date, 
                    customer_name, customer_email, customer_phone,
                    total_amount, ticket_data, generated_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $ticket_id,
                $data['ticket_type'],
                $data['pnr'] ?? 'N/A',
                $data['booking_date'] ?? date('Y-m-d'),
                $data['customer_name'],
                $data['customer_email'] ?? null,
                $data['customer_phone'] ?? null,
                $data['total_amount'],
                $ticket_data_json,
                $user_id
            ]);

            return [
                'success' => true,
                'ticket_id' => $ticket_id,
                'message' => 'Ticket saved successfully'
            ];

        } catch (PDOException $e) {
            error_log("Save ticket error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    private function generateTicketId($type) {
        $prefix = strtoupper(substr($type, 0, 1));
        $date = date('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }

    private function prepareTicketData($data) {
        $ticket_data = $data;
        $ticket_data['generated_by'] = $_SESSION['username'] ?? 'System';
        $ticket_data['generated_at'] = date('Y-m-d H:i:s');
        $ticket_data['ticket_id'] = $this->generateTicketId($data['ticket_type']);

        // Add passenger array if exists
        if (isset($data['passenger_name']) && is_array($data['passenger_name'])) {
            $passengers = [];
            $count = count($data['passenger_name']);

            for ($i = 0; $i < $count; $i++) {
                $passenger = [
                    'name' => $data['passenger_name'][$i] ?? '',
                    'age' => $data['passenger_age'][$i] ?? '',
                    'gender' => $data['passenger_gender'][$i] ?? '',
                    'id_type' => $data['passenger_id_type'][$i] ?? '',
                    'id_number' => $data['passenger_id_number'][$i] ?? '',
                    'seat' => $data['passenger_seat'][$i] ?? '',
                    'coach' => $data['passenger_coach'][$i] ?? ''
                ];
                $passengers[] = $passenger;
            }
            $ticket_data['passengers'] = $passengers;
        }

        return $ticket_data;
    }

    public function getTicketByIdentifier($identifier) {
        try {
            // First try to get by ticket_id
            $stmt = $this->db->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
            $stmt->execute([$identifier]);
            $ticket = $stmt->fetch();

            // If not found, try by database id
            if (!$ticket) {
                $stmt = $this->db->prepare("SELECT * FROM tickets WHERE id = ?");
                $stmt->execute([$identifier]);
                $ticket = $stmt->fetch();
            }

            if ($ticket && is_string($ticket['ticket_data'])) {
                $ticket['ticket_data'] = json_decode($ticket['ticket_data'], true);
            }

            return $ticket;

        } catch (PDOException $e) {
            error_log("Get ticket error: " . $e->getMessage());
            return null;
        }
    }

    public function getTicket($ticket_id) {
        return $this->getTicketByIdentifier($ticket_id);
    }
}
?>
