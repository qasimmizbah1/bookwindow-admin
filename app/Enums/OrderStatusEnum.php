<?php 

namespace App\Enums;

enum OrderStatusEnum: string
{
    case NEW = 'new';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case ORDER_SHIPPED = 'order_shipped';
    case COMPLETED = 'completed';
    case DECLINED = 'declined';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
}

?>