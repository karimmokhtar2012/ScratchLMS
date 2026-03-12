<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Notifications\Notification;

/**
 * Notification Service
 * Centralizes notification creation logic
 */
class NotificationService
{
    private Notification $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new Notification();
    }

    /**
     * Send welcome notification to new user
     *
     * @param int $userId
     * @return void
     */
    public function sendWelcome(int $userId): void
    {
        $this->notificationModel->create([
            'user_id' => $userId,
            'type' => 'system',
            'title' => 'Welcome to ScratchLMS',
            'message' => "We're glad you're here! Start exploring courses now.",
            'link' => '/courses'
        ]);
    }

    /**
     * Send course enrollment notification
     *
     * @param int $userId
     * @param string $courseTitle
     * @param int $courseId
     * @return void
     */
    public function sendCourseEnrollment(int $userId, string $courseTitle, int $courseId): void
    {
        $this->notificationModel->create([
            'user_id' => $userId,
            'type' => 'enrollment',
            'title' => 'Enrolled Successfully',
            'message' => "You've been enrolled in: $courseTitle",
            'link' => "/learn/$courseId"
        ]);
    }

    /**
     * Send quiz attempt approval notification
     *
     * @param int $userId
     * @param string $quizTitle
     * @param int $quizId
     * @return void
     */
    public function sendQuizApproval(int $userId, string $quizTitle, int $quizId): void
    {
        $this->notificationModel->create([
            'user_id' => $userId,
            'type' => 'system',
            'title' => 'Request Approved',
            'message' => "Your request to retake '$quizTitle' has been approved. Good luck!",
            'link' => "/learn/quiz/intro/$quizId"
        ]);
    }

    /**
     * Send quiz attempt rejection notification
     *
     * @param int $userId
     * @param string $quizTitle
     * @param int $quizId
     * @return void
     */
    public function sendQuizRejection(int $userId, string $quizTitle, int $quizId): void
    {
        $this->notificationModel->create([
            'user_id' => $userId,
            'type' => 'system',
            'title' => 'Request Rejected',
            'message' => "Your request to retake '$quizTitle' was rejected.",
            'link' => "/learn/quiz/intro/$quizId"
        ]);
    }

    /**
     * Send certificate issued notification
     *
     * @param int $userId
     * @param string $courseTitle
     * @param string $certificateId
     * @return void
     */
    public function sendCertificateIssued(int $userId, string $courseTitle, string $certificateId): void
    {
        $this->notificationModel->create([
            'user_id' => $userId,
            'type' => 'achievement',
            'title' => '🎉 Certificate Issued!',
            'message' => "Congratulations! Your certificate for '$courseTitle' is ready.",
            'link' => "/certificates/$certificateId"
        ]);
    }
}
