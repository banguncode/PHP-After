<?php

namespace PHPFrista;

class StatusCode
{
    const OK                    = 'OK';
    const ALREADY_REGISTERED    = 'ALREADY_REGISTERED';
    const INVALID_ID            = 'INVALID_ID';
    const INVALID_PARTICIPANT   = 'INVALID_PARTICIPANT';
    const INVALID_MINUTIAE      = 'INVALID_MINUTIAE';
    const INVALID_IMAGE         = 'INVALID_IMAGE';
    const AUTH_FAILED           = 'AUTH_FAILED';
    const SERVER_UNREACHABLE    = 'SERVER_UNREACHABLE';
    const INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';
    const INTEGRATION_ERROR     = 'INTEGRATION_ERROR';
    const NOT_RECOGNIZED        = 'NOT_RECOGNIZED';
    const NOT_REGISTERED        = 'NOT_REGISTERED';
    const NOT_ENROLLED          = 'NOT_ENROLLED';
}
