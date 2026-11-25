<?php

namespace App\Enums;

/**
 * A code issued for one purpose can never be used for another.
 */
enum OtpPurpose: string
{
    case Registration = 'registration';
    case Activation = 'activation';
    case TwoFactor = 'two_factor';
    case PasswordReset = 'password_reset';
}
