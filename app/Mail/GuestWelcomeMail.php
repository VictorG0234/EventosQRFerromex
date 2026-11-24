<?php

namespace App\Mail;

use App\Models\Guest;
use Illuminate\Contracts\Queue\ShouldQueue;

class GuestWelcomeMail extends GuestInvitationMail implements ShouldQueue
{
    public function __construct(Guest $guest)
    {
        parent::__construct($guest, $guest->event);
    }
}
