<?php

declare(strict_types=1);

namespace App\Services;

final class ComplianceValidator
{
    /** @var array<string, string> */
    private const RULES = [
        'minor' => '/\b(minors?|children|child|under[\s-]?age|underage|teens?|teenagers?|pre[\s-]?teens?|lolita|schoolgirls?|jailbait|paedo\w*|pedo\w*)\b/i',
        'coercion' => '/\b(traffick\w*|coerc\w*|non[\s-]?consensual|rape|raped|kidnap\w*|abduct\w*|slavery|forced)\b/i',
        'illegal_service' => '/\b(cocaine|heroin|methamphetamine|fentanyl)\b/i',
        'credentials' => '/\b(licensed|certified|award|guaranteed?|five[\s-]?star|board[\s-]?certified|verified identity|medical degree)\b/i',
        'explicit' => '/\b(porn|xxx|nudes?|naked|blowjob|handjob|intercourse|orgasm|erotic|happy ending)\b/i',
        'under_21' => '/\b(?:age\s*)?(?:[0-9]|1[0-9]|20)(?!\d)\s*(?:years?\s*old|yo|yrs?\s*old)\b|\bage\s*(?:[0-9]|1[0-9]|20)\b/i',
    ];

    public function failureReason(string $text, int $age): ?string
    {
        if ($age < 21 || $age > 99) {
            return 'age_out_of_range';
        }

        foreach (self::RULES as $code => $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return $code;
            }
        }

        if (preg_match('/\b(?:\+?\d[\d\s\-()]{8,}\d)\b/', $text) === 1) {
            return 'phone_number';
        }

        return null;
    }
}
