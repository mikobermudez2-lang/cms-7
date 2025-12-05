<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Email configuration - update these with your SMTP settings
 */
const SMTP_HOST = 'smtp.gmail.com'; // Change to your SMTP server
const SMTP_PORT = 587;
const SMTP_USERNAME = 'phcornertv@gmail.com'; // Your email address
const SMTP_PASSWORD = 'tpwp vayz clog htxy'; // Your email password or app password
const SMTP_FROM_EMAIL = 'noreply@healthcarecenter.com';
const SMTP_FROM_NAME = 'Healthcare Center';

/**
 * Creates and configures a PHPMailer instance.
 */
function create_mailer(): PHPMailer
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // From address
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

        return $mail;
    } catch (Exception $e) {
        error_log("PHPMailer configuration error: {$e->getMessage()}");
        throw $e;
    }
}

/**
 * Sends an appointment status update email to a patient.
 */
function send_appointment_confirmation(string $patientEmail, string $patientName, array $appointmentDetails): bool
{
    if (empty(SMTP_USERNAME) || empty(SMTP_PASSWORD)) {
        error_log('SMTP credentials not configured. Email not sent.');
        return false;
    }

    try {
        $mail = create_mailer();
        $mail->addAddress($patientEmail, $patientName);
        $mail->isHTML(true);

        $date = $appointmentDetails['date'] ? date('F d, Y', strtotime($appointmentDetails['date'])) : 'To be scheduled';
        $time = $appointmentDetails['time'] ? date('h:i A', strtotime($appointmentDetails['time'])) : 'To be scheduled';
        $doctorName = $appointmentDetails['doctor_name'] ?? 'Your doctor';
        $status = $appointmentDetails['status'] ?? 'Waiting';

        // Customize email based on status
        $statusConfig = [
            'Waiting' => [
                'subject' => 'Appointment Request Received - Healthcare Center',
                'headerColor' => '#ffc107',
                'headerText' => 'Appointment Request Received',
                'message' => 'Your appointment request has been received and is currently <strong>Waiting</strong> for confirmation. Our staff will review your request and assign a date and time. You will receive another notification once your appointment is confirmed.',
                'additionalInfo' => 'We will contact you soon with your scheduled appointment details.'
            ],
            'Confirmed' => [
                'subject' => 'Appointment Confirmed - Healthcare Center',
                'headerColor' => '#198754',
                'headerText' => 'Appointment Confirmed',
                'message' => 'Your appointment has been <strong>Confirmed</strong>. Please arrive 15 minutes before your scheduled time.',
                'additionalInfo' => 'If you need to reschedule or cancel, please contact us at least 24 hours in advance.'
            ],
            'Completed' => [
                'subject' => 'Appointment Completed - Healthcare Center',
                'headerColor' => '#0d6efd',
                'headerText' => 'Appointment Completed',
                'message' => 'Your appointment has been marked as <strong>Completed</strong>. Thank you for visiting Healthcare Center.',
                'additionalInfo' => 'If you have any follow-up questions or need to schedule another appointment, please don\'t hesitate to contact us.'
            ],
            'Rejected' => [
                'subject' => 'Appointment Update - Healthcare Center',
                'headerColor' => '#dc3545',
                'headerText' => 'Appointment Update',
                'message' => 'We regret to inform you that your appointment request has been <strong>Rejected</strong>.',
                'additionalInfo' => 'Please contact us if you have any questions or would like to reschedule. We apologize for any inconvenience.'
            ],
            'Cancelled' => [
                'subject' => 'Appointment Cancelled - Healthcare Center',
                'headerColor' => '#6c757d',
                'headerText' => 'Appointment Cancelled',
                'message' => 'Your appointment has been <strong>Cancelled</strong>.',
                'additionalInfo' => 'If you would like to schedule a new appointment, please contact us or book online through our website.'
            ]
        ];

        $config = $statusConfig[$status] ?? $statusConfig['Waiting'];
        
        $mail->Subject = $config['subject'];

        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: {$config['headerColor']}; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; }
                .details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid {$config['headerColor']}; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>{$config['headerText']}</h2>
                </div>
                <div class='content'>
                    <p>Dear {$patientName},</p>
                    <p>{$config['message']}</p>
                    <div class='details'>
                        <p><strong>Doctor:</strong> {$doctorName}</p>
                        <p><strong>Date:</strong> {$date}</p>
                        <p><strong>Time:</strong> {$time}</p>
                        <p><strong>Status:</strong> {$status}</p>
                    </div>
                    <p>{$config['additionalInfo']}</p>
                    <p>Thank you for choosing Healthcare Center.</p>
                </div>
                <div class='footer'>
                    <p>© 2025 Healthcare Center. All rights reserved.</p>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->AltBody = "Dear {$patientName},\n\n{$config['message']}\n\nDoctor: {$doctorName}\nDate: {$date}\nTime: {$time}\nStatus: {$status}\n\n{$config['additionalInfo']}\n\nThank you for choosing Healthcare Center.";

        $mail->send();
        error_log("Appointment status email ({$status}) sent successfully to: {$patientEmail}");
        return true;
    } catch (Exception $e) {
        $errorMsg = "Failed to send appointment status email to {$patientEmail}: {$e->getMessage()}";
        error_log($errorMsg);
        error_log("PHPMailer ErrorInfo: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Sends a notification email when a new appointment request is received.
 */
function send_appointment_request_notification(string $adminEmail, array $appointmentDetails): bool
{
    if (empty(SMTP_USERNAME) || empty(SMTP_PASSWORD)) {
        return false;
    }

    try {
        $mail = create_mailer();
        $mail->addAddress($adminEmail);
        $mail->isHTML(true);
        $mail->Subject = 'New Appointment Request - Healthcare Center';

        $patientName = $appointmentDetails['patient_name'] ?? 'Unknown';
        $doctorName = $appointmentDetails['doctor_name'] ?? 'Unknown';
        $patientEmail = $appointmentDetails['patient_email'] ?? '';
        $patientPhone = $appointmentDetails['patient_phone'] ?? '';

        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; }
                .details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #dc3545; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Appointment Request</h2>
                </div>
                <div class='content'>
                    <p>A new appointment request has been submitted:</p>
                    <div class='details'>
                        <p><strong>Patient:</strong> {$patientName}</p>
                        <p><strong>Email:</strong> {$patientEmail}</p>
                        <p><strong>Phone:</strong> {$patientPhone}</p>
                        <p><strong>Doctor:</strong> {$doctorName}</p>
                        <p><strong>Status:</strong> Waiting (Schedule pending)</p>
                    </div>
                    <p>Please log in to the admin panel to assign a date and time for this appointment.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->send();
        error_log("Appointment request notification email sent successfully to: {$adminEmail}");
        return true;
    } catch (Exception $e) {
        $errorMsg = "Failed to send appointment request notification to {$adminEmail}: {$e->getMessage()}";
        error_log($errorMsg);
        error_log("PHPMailer ErrorInfo: " . $mail->ErrorInfo);
        return false;
    }
}

