<?php
$file = 'c:\\Users\\VIP\\Desktop\\sami-care\\sami-care\\Modules\\Booking\\Trait\\BookingTrait.php';
$content = file_get_contents($file);

$search = "        if (\$notify) {
            BulkNotification::dispatch(\$data);
        } else {
            return \$data;
        }
    }
}";

$replace = "        if (\$type === 'new_booking') {
            try {
                app(\App\Services\TaqnyatSmsService::class)->sendBookingCreatedNotification(\$booking->id);
            } catch (\Exception \$e) {
                \Illuminate\Support\Facades\Log::error('WhatsApp booking confirmation failed: ' . \$e->getMessage());
            }
        }

        if (\$notify) {
            BulkNotification::dispatch(\$data);
        } else {
            return \$data;
        }
    }
}";

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Replaced successfully.";
