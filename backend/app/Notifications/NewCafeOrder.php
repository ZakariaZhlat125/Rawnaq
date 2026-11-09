<?php

namespace App\Notifications;

use App\Models\CafeOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCafeOrder extends Notification
{
    use Queueable;

    public function __construct(private readonly CafeOrder $order)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'module' => 'CafeOrder',
            'type' => 'created',
            'icon' => 'fa-solid fa-mug-hot',
            'title' => __('cafe.new_order_notification_title'),
            'text' => __('cafe.new_order_notification_text', [
                'number' => $this->order->order_number,
                'table' => $this->order->table?->name ?? $this->order->cafe_table_id,
            ]),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => (float) $this->order->total,
            'url_backend' => route('backend.cafe.orders.show', $this->order->id),
        ];
    }
}
