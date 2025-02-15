<?php

namespace App\Services;

use App\Models\User;
use App\Models\Case;
use App\Models\Patient;
use App\Models\Notification;
use Illuminate\Support\Facades\Broadcast;
use App\Events\ClinicalAlert;
use App\Events\TeamNotification;

class NotificationService
{
    protected $priorityLevels = [
        'emergency' => 1,
        'urgent' => 2,
        'high' => 3,
        'medium' => 4,
        'low' => 5
    ];

    public function sendClinicalAlert($data)
    {
        $notification = new Notification([
            'type' => 'clinical_alert',
            'priority' => $data['priority'],
            'content' => $data['content'],
            'metadata' => [
                'patient_id' => $data['patient_id'],
                'case_id' => $data['case_id'],
                'alert_type' => $data['alert_type'],
                'triggered_by' => $data['triggered_by']
            ]
        ]);

        // Save notification
        $notification->save();

        // Get team members who should receive this alert
        $recipients = $this->getAlertRecipients($data);

        foreach ($recipients as $recipient) {
            // Create user-specific notification
            $userNotification = $notification->userNotifications()->create([
                'user_id' => $recipient->id,
                'status' => 'pending'
            ]);

            // Broadcast to specific user's channel
            broadcast(new ClinicalAlert($userNotification))->toOthers();

            // Send additional notifications based on priority and user preferences
            $this->sendAdditionalNotifications($recipient, $notification);
        }

        return $notification;
    }

    public function sendTeamUpdate($data)
    {
        $notification = new Notification([
            'type' => 'team_update',
            'priority' => $data['priority'],
            'content' => $data['content'],
            'metadata' => [
                'case_id' => $data['case_id'],
                'update_type' => $data['update_type'],
                'triggered_by' => $data['triggered_by']
            ]
        ]);

        $notification->save();

        // Broadcast to team channel
        broadcast(new TeamNotification($notification))->toOthers();

        return $notification;
    }

    protected function getAlertRecipients($data)
    {
        $case = Case::find($data['case_id']);
        $recipients = collect();

        // Get team members based on role and alert type
        switch ($data['alert_type']) {
            case 'critical_lab_result':
                $recipients = $case->team->users()
                    ->whereIn('role', ['lead', 'physician', 'nurse'])
                    ->get();
                break;

            case 'medication_interaction':
                $recipients = $case->team->users()
                    ->whereIn('role', ['physician', 'pharmacist'])
                    ->get();
                break;

            case 'vital_signs_alert':
                $recipients = $case->team->users()
                    ->whereIn('role', ['physician', 'nurse'])
                    ->get();
                break;

            default:
                $recipients = $case->team->users()->get();
        }

        return $recipients;
    }

    protected function sendAdditionalNotifications(User $user, Notification $notification)
    {
        // Check user preferences
        $preferences = $user->notificationPreferences;

        if ($this->shouldSendUrgentNotification($notification)) {
            if ($preferences->sms_enabled) {
                $this->sendSMS($user->phone, $notification);
            }

            if ($preferences->email_enabled) {
                $this->sendEmail($user->email, $notification);
            }
        }
    }

    protected function shouldSendUrgentNotification(Notification $notification)
    {
        return isset($this->priorityLevels[$notification->priority]) &&
               $this->priorityLevels[$notification->priority] <= $this->priorityLevels['high'];
    }

    protected function sendSMS($phoneNumber, Notification $notification)
    {
        // Implement SMS sending logic
        $message = $this->formatSMSMessage($notification);
        // Use SMS service provider
    }

    protected function sendEmail($email, Notification $notification)
    {
        // Implement email sending logic
        $emailData = $this->formatEmailContent($notification);
        // Use Laravel's mail system
    }

    protected function formatSMSMessage(Notification $notification)
    {
        // Format notification content for SMS
        $message = sprintf(
            "[%s] %s - %s",
            strtoupper($notification->priority),
            $notification->type,
            substr($notification->content, 0, 140)
        );

        return $message;
    }

    protected function formatEmailContent(Notification $notification)
    {
        // Format notification content for email
        return [
            'subject' => sprintf(
                "[%s] Clinical Alert - %s",
                strtoupper($notification->priority),
                $notification->type
            ),
            'content' => $notification->content,
            'metadata' => $notification->metadata
        ];
    }
}

// app/Events/ClinicalAlert.php
namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ClinicalAlert implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $notification;

    public function __construct($notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn()
    {
        return new Channel('user.' . $this->notification->user_id);
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'priority' => $this->notification->priority,
            'content' => $this->notification->content,
            'metadata' => $this->notification->metadata,
            'created_at' => $this->notification->created_at->toIso8601String()
        ];
    }
}

// app/Events/TeamNotification.php
namespace App\Events;

class TeamNotification implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $notification;

    public function __construct($notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn()
    {
        return new Channel('team.' . $this->notification->metadata['case_id']);
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'priority' => $this->notification->priority,
            'content' => $this->notification->content,
            'metadata' => $this->notification->metadata,
            'created_at' => $this->notification->created_at->toIso8601String()
        ];
    }
}