<?php

namespace App\Services\Gifts;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Short, human-typable redeem codes like SAMI-7K4Q-9XPM.
 *
 * 8 random characters from a 31-symbol alphabet without look-alikes (0/O, 1/I/L)
 * give ~8.5e11 combinations; with the redeem endpoint throttled, guessing is impractical.
 * The code is stored as an HMAC (keyed with APP_KEY) for lookup, plus an encrypted copy
 * so staff and the buyer can see or resend it. The plain code is never stored.
 */
class GiftCodeGenerator
{
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    private const PREFIX = 'SAMI';

    public function generate(): string
    {
        $chars = '';
        for ($i = 0; $i < 8; $i++) {
            $chars .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return self::PREFIX.'-'.substr($chars, 0, 4).'-'.substr($chars, 4, 4);
    }

    /** Columns to store for a fresh, unused code. */
    public function generateColumns(): array
    {
        do {
            $code = $this->generate();
            $hash = $this->hash($code);
        } while (DB::table('gift_cards')->where('redeem_code_hash', $hash)->exists());

        return [
            'redeem_code_hash' => $hash,
            'redeem_code_encrypted' => Crypt::encryptString($code),
            'redeem_code_last4' => substr($code, -4),
        ];
    }

    /** Accepts "sami-7k4q 9xpm", "7K4Q9XPM", "SAMI7K4Q9XPM" ... */
    public function normalize(string $input): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input));
        if (str_starts_with($clean, self::PREFIX) && strlen($clean) === strlen(self::PREFIX) + 8) {
            $clean = substr($clean, strlen(self::PREFIX));
        }

        return $clean;
    }

    public function hash(string $code): string
    {
        return hash_hmac('sha256', $this->normalize($code), (string) config('app.key'));
    }

    public function looksValid(string $input): bool
    {
        return (bool) preg_match('/^['.self::ALPHABET.']{8}$/', $this->normalize($input));
    }
}
