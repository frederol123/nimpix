<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueuedVerifyEmailNotification extends VerifyEmail implements ShouldQueue {}
