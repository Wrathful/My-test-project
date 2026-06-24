<?php

namespace Modules\Notification\Enums;

enum NotificationRecipientStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
}