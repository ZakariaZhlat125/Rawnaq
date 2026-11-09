<?php

namespace App\Services;

use App\Models\Setting;

class FaqContent
{
    public function all(): array
    {
        $rows = json_decode(Setting::get('faq_content', '[]'), true);
        return is_array($rows) ? array_values($rows) : [];
    }

    public function published(): array
    {
        return array_values(array_filter($this->all(), fn ($row) => !empty($row['active'])));
    }
}
