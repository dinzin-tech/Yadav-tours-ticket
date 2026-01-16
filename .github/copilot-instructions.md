# AI Coding Agent Instructions for Yadav Tours Ticket System

## Project Overview
This is a PHP-based travel ticket management system for Yadav Tours. It generates and manages tickets for trains, flights, buses, cabs, and tours. The system uses MySQL for data storage and Dompdf for PDF generation.

## Architecture
- **MVC Structure**: Controllers in `app/controllers/`, models in `app/models/`, views/templates in `templates/`
- **Database**: PDO with MySQL, ticket data stored as JSON in `tickets` table
- **Authentication**: Session-based with failed login protection and account lockout
- **Ticket Generation**: HTML templates per type, PDF export using Dompdf
- **Branding**: Customizable agency branding via `BrandingSettings` class

## Key Components
- **Ticket Types**: train, flight, bus, cab, tour - each with specific validation and templates
- **Ticket ID Format**: `{Type[0]}{YYYYMMDD}{4-digit sequence}` (e.g., T202601150001)
- **Security**: CSRF tokens, input sanitization, PNR validation per type
- **Audit System**: All ticket operations logged in `audit_logs` table

## Development Workflow
1. **Setup**: Import `ticket_system.sql`, configure DB credentials in `app/config/database.php`
2. **Run Locally**: Use PHP built-in server `php -S localhost:8000 -t public/`
3. **Create Tickets**: Access `public/create_ticket.php?type={train|flight|bus|cab|tour}`
4. **Generate PDFs**: Use `PDFGenerator` class with ticket data and agency details

## Code Patterns
- **Database Access**: Use `getDB()` function for PDO connections
- **Path Constants**: `BASE_PATH`, `APP_PATH`, `PUBLIC_PATH`, `TEMPLATES_PATH`
- **Session Checks**: Always verify `$_SESSION['logged_in'] === true` before protected pages
- **Error Handling**: Return associative arrays with `success` boolean and `message` string
- **Ticket Data**: Store complex data as JSON, decode when displaying
- **Validation**: Use `Security::validatePNR($pnr, $type)` for PNR format checks

## File Structure Examples
- **Controllers**: `TicketController::saveTicket()` handles DB insertion with JSON encoding
- **Templates**: `TicketTemplates::generateHTML()` switches on `$ticket['ticket_type']`
- **Forms**: Dynamic passenger arrays in `create_ticket.php` using indexed inputs
- **PDF Generation**: `PDFGenerator` loads HTML templates and renders to PDF

## Common Tasks
- **Add Ticket Type**: Create new case in template switches, add validation in Security class
- **Modify Branding**: Update `branding_settings` table and `BrandingSettings` class
- **Database Queries**: Use prepared statements, fetch as associative arrays
- **Session Management**: Store user_id, username, role; regenerate on login

## Dependencies
- **PHP 8.0+**: Required for modern features
- **MySQL/MariaDB**: Database with JSON column support
- **Dompdf**: For PDF generation (install via Composer)
- **No Composer Packages**: Currently minimal dependencies, but Dompdf expected

## Security Notes
- **Input Sanitization**: Always use `Security::sanitizeInput()` or `htmlspecialchars()`
- **CSRF Protection**: Include `$_SESSION['csrf_token']` in forms
- **Password Hashing**: Use `password_verify()` for login checks
- **Session Security**: Regenerate session ID on login, store sessions in DB if needed

## Testing
- **Manual Testing**: Create tickets via forms, verify PDF generation
- **Database Checks**: Query `tickets` table for JSON data integrity
- **Validation Testing**: Test PNR formats and required fields per ticket type</content>
<parameter name="filePath">c:\Projects\DINZIN-Projects\Yadav-tours-ticket\.github\copilot-instructions.md