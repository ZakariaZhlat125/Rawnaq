<?php

namespace App\Services;

use App\Models\BookingReview;
use Modules\Employee\Models\EmployeeRating;

class EmployeeReviewSummary
{
    /** One customer vote per booking and employee; modern service votes take precedence. */
    public static function merge(array $modern, array $legacy): array
    {
        $votes = [];
        foreach ($modern as $review) {
            foreach ($review['service_ratings'] ?? [] as $row) {
                if (empty($row['employee_id']) || !isset($row['employee_rating'])) continue;
                $key = $review['booking_id'].':'.$review['user_id'].':'.$row['employee_id'];
                $votes[$key] ??= ['employee_id' => (int) $row['employee_id'], 'booking_id' => $review['booking_id'], 'user_id' => $review['user_id'], 'ratings' => [], 'comment' => $review['review_text'] ?? '', 'date' => $review['created_at'] ?? null];
                $votes[$key]['ratings'][] = (float) $row['employee_rating'];
            }
        }
        foreach ($legacy as $review) {
            $key = ($review['booking_id'] ?? 'legacy-'.$review['id']).':'.$review['user_id'].':'.$review['employee_id'];
            $votes[$key] ??= ['employee_id' => (int) $review['employee_id'], 'booking_id' => $review['booking_id'] ?? null, 'user_id' => $review['user_id'], 'ratings' => [(float) $review['rating']], 'comment' => $review['review_msg'] ?? '', 'date' => $review['created_at'] ?? null];
        }
        return array_values(array_map(function ($vote) {
            $vote['rating'] = array_sum($vote['ratings']) / count($vote['ratings']);
            unset($vote['ratings']);
            return $vote;
        }, $votes));
    }

    public function votes(array $employeeIds): array
    {
        $modern = BookingReview::whereNotNull('service_ratings')->get(['booking_id', 'user_id', 'service_ratings', 'review_text', 'created_at'])->toArray();
        $legacy = EmployeeRating::whereIn('employee_id', $employeeIds)->get()->toArray();
        return array_values(array_filter(self::merge($modern, $legacy), fn ($vote) => in_array($vote['employee_id'], $employeeIds)));
    }

    public function summaries(array $employeeIds): array
    {
        $result = [];
        foreach ($this->votes($employeeIds) as $vote) {
            $id = $vote['employee_id'];
            $result[$id] ??= ['sum' => 0, 'count' => 0, 'average' => 0];
            $result[$id]['sum'] += $vote['rating'];
            $result[$id]['count']++;
            $result[$id]['average'] = $result[$id]['sum'] / $result[$id]['count'];
        }
        return $result;
    }
}
