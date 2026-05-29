<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public function send(int $userId, string $type, string $title, string $body = null, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    public function newMessage(int $recipientId, string $senderName, int $conversationId): void
    {
        $this->send(
            $recipientId,
            'new_message',
            'Nowa wiadomość',
            "Masz nową wiadomość od {$senderName}.",
            ['conversation_id' => $conversationId]
        );
    }

    public function viewingAccepted(int $userId, string $propertyTitle, int $viewingId): void
    {
        $this->send(
            $userId,
            'viewing_accepted',
            'Rezerwacja zaakceptowana',
            "Twoja rezerwacja oglądania \"{$propertyTitle}\" została zaakceptowana.",
            ['viewing_id' => $viewingId]
        );
    }

    public function viewingRejected(int $userId, string $propertyTitle, int $viewingId): void
    {
        $this->send(
            $userId,
            'viewing_rejected',
            'Rezerwacja odrzucona',
            "Twoja rezerwacja oglądania \"{$propertyTitle}\" została odrzucona.",
            ['viewing_id' => $viewingId]
        );
    }

    public function newViewing(int $ownerId, string $userName, string $propertyTitle, int $viewingId): void
    {
        $this->send(
            $ownerId,
            'new_viewing',
            'Nowa rezerwacja oglądania',
            "{$userName} chce zobaczyć \"{$propertyTitle}\".",
            ['viewing_id' => $viewingId]
        );
    }

    public function contractGenerated(int $userId, string $propertyTitle, int $contractId): void
    {
        $this->send(
            $userId,
            'contract_generated',
            'Umowa wygenerowana',
            "Wygenerowano umowę dla \"{$propertyTitle}\".",
            ['contract_id' => $contractId]
        );
    }

    public function paymentCreated(int $userId, string $amount, int $paymentId): void
    {
        $this->send(
            $userId,
            'payment_created',
            'Nowa płatność',
            "Wystawiono płatność na kwotę {$amount} zł.",
            ['payment_id' => $paymentId]
        );
    }

    public function paymentStatusChanged(int $userId, string $status, int $paymentId): void
    {
        $this->send(
            $userId,
            'payment_status_changed',
            'Zmiana statusu płatności',
            "Status Twojej płatności zmienił się na: {$status}.",
            ['payment_id' => $paymentId]
        );
    }
}
