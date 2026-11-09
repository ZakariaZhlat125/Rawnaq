<?php
namespace App\Services;

class ReviewReport
{
    public static function rows(array $reviews, array $legacy = []): array
    {
        $rows = []; $employeeVotes = [];
        foreach ($reviews as $review) {
            $booking = $review['booking'] ?? [];
            $base = ['booking_id'=>$review['booking_id'], 'customer'=>$review['user']['full_name'] ?? trim(($review['user']['first_name'] ?? '').' '.($review['user']['last_name'] ?? '')),
                'branch_id'=>$booking['branch_id'] ?? null, 'branch'=>$booking['branch']['name'] ?? '', 'date'=>$review['created_at'],
                'approved'=>(bool)$review['is_approved'], 'visible'=>(bool)$review['status'], 'comment'=>$review['review_text'] ?? '', 'legacy'=>false];
            if (!empty($review['branch_rating'])) $rows[] = $base + ['type'=>'branch','target'=>$base['branch'],'rating'=>(float)$review['branch_rating']];
            else $rows[] = $base + ['type'=>'booking','target'=>'#'.$review['booking_id'],'rating'=>(float)$review['rating']];
            $services = array_column($booking['services'] ?? [], null, 'id');
            foreach ($review['service_ratings'] ?? [] as $vote) {
                $service = $services[$vote['booking_service_id'] ?? 0] ?? [];
                if (isset($vote['service_rating'])) $rows[] = $base + ['type'=>'service','target'=>$service['service_name'] ?? '#'.($vote['service_id'] ?? '—'),'rating'=>(float)$vote['service_rating']];
                if (!empty($vote['employee_id']) && isset($vote['employee_rating'])) {
                    $employeeVotes[$review['booking_id'].':'.$review['user_id'].':'.$vote['employee_id']] = true;
                    $rows[] = $base + ['type'=>'employee','target'=>$service['employee']['full_name'] ?? '#'.$vote['employee_id'],'rating'=>(float)$vote['employee_rating']];
                }
            }
        }
        foreach ($legacy as $vote) {
            if (isset($employeeVotes[($vote['booking_id'] ?? '').':'.$vote['user_id'].':'.$vote['employee_id']])) continue;
            $booking = $vote['booking'] ?? [];
            $rows[] = ['type'=>'employee','target'=>$vote['employee']['full_name'] ?? '#'.$vote['employee_id'], 'rating'=>(float)$vote['rating'],
                'booking_id'=>$vote['booking_id'] ?? null, 'customer'=>$vote['user']['full_name'] ?? '', 'branch_id'=>$booking['branch_id'] ?? null,
                'branch'=>$booking['branch']['name'] ?? '', 'date'=>$vote['created_at'], 'approved'=>null, 'visible'=>true,'comment'=>$vote['review_msg'] ?? '', 'legacy'=>true];
        }
        return array_values(array_filter($rows, fn($r) => $r['rating'] >= 1 && $r['rating'] <= 5));
    }

    public static function filter(array $rows, array $filters): array
    {
        return array_values(array_filter($rows, function ($row) use ($filters) {
            if (!empty($filters['type']) && $row['type'] !== $filters['type']) return false;
            if (!empty($filters['rating']) && $row['rating'] != $filters['rating']) return false;
            if (!empty($filters['branch_id']) && $row['branch_id'] != $filters['branch_id']) return false;
            if (($filters['approval'] ?? '') === 'approved' && $row['approved'] !== true) return false;
            if (($filters['approval'] ?? '') === 'pending' && $row['approved'] !== false) return false;
            if (($filters['visibility'] ?? '') === 'visible' && !$row['visible']) return false;
            if (($filters['visibility'] ?? '') === 'hidden' && $row['visible']) return false;
            return true;
        }));
    }

    public static function name($value): string
    {
        if (is_string($value)) { $decoded=json_decode($value,true); if(is_array($decoded)) $value=$decoded; }
        return is_array($value) ? (string)($value[app()->getLocale()] ?? $value['ar'] ?? $value['en'] ?? reset($value) ?: '—') : (string)($value ?: '—');
    }
}
