<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Helpers;

class DateHelper
{
    public static function format(?string $date, string $format = DATE_FORMAT): string
    {
        if (!$date) return '';
        try {
            return (new \DateTime($date))->format($format);
        } catch (\Throwable) {
            return '';
        }
    }

    public static function diff(\DateTime $from, ?\DateTime $to = null): \DateInterval
    {
        return $from->diff($to ?? new \DateTime());
    }

    public static function isExpired(string $datetime): bool
    {
        try {
            return new \DateTime($datetime) < new \DateTime();
        } catch (\Throwable) {
            return true;
        }
    }

    public static function addHours(int $hours, ?\DateTime $from = null): \DateTime
    {
        $dt = $from ? clone $from : new \DateTime();
        return $dt->modify("+{$hours} hours");
    }
}
