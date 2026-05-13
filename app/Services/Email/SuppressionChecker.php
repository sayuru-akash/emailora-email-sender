<?php

namespace App\Services\Email;

use App\Models\Contact;
use App\Models\EmailSuppression;

final class SuppressionChecker
{
    public function canSend(Contact $contact): bool
    {
        if (! in_array($contact->status, ['active'], true)) {
            return false;
        }

        return ! EmailSuppression::query()->where('email_normalized', $contact->email_normalized)->exists();
    }
}
