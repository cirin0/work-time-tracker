<?php

namespace App\Enums;

enum EntryType: string
{
    case GPS = 'gps';
    case QR = 'qr';
    case GPS_QR = 'gps_qr';
    case REMOTE = 'remote';
    case MANUAL = 'manual';
}
