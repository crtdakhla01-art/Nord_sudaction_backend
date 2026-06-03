<?php

namespace App\Services\Email;

class EmailDeliveryResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $messageId = null,
        public readonly ?string $errorMessage = null,
        public readonly array $errorPayload = [],
    ) {
    }

    public static function submitted(?string $messageId = null): self
    {
        return new self(
            success: true,
            status: 'submitted',
            messageId: $messageId,
        );
    }

    public static function sent(?string $messageId = null): self
    {
        return new self(
            success: true,
            status: 'sent',
            messageId: $messageId,
        );
    }

    public static function failed(string $errorMessage, array $errorPayload = []): self
    {
        return new self(
            success: false,
            status: 'failed',
            errorMessage: $errorMessage,
            errorPayload: $errorPayload,
        );
    }

    public function normalizedErrorText(): string
    {
        if ($this->errorMessage !== null && trim($this->errorMessage) !== '') {
            return $this->errorMessage;
        }

        if (! empty($this->errorPayload)) {
            return json_encode($this->errorPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return '';
    }
}
