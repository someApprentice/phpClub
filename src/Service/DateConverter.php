<?php

declare(strict_types=1);

namespace phpClub\Service;

class DateConverter
{
    /**
     * @param string $date
     * @return \DateTimeImmutable
     * @throws \Exception
     */
    public function toDateTime(string $date): \DateTimeImmutable
    {
        $normalized = $this->normalizeDate($date);

        $dateTime = \DateTimeImmutable::createFromFormat('d M Y H:i:s', $normalized);
        if ($dateTime !== false) {
            return $dateTime;
        }

        $dateTime = \DateTimeImmutable::createFromFormat('d/m/y  H:i:s', $normalized);
        if ($dateTime !== false) {
            return $dateTime;
        }

        throw new \Exception("Unable to parse date: {$date}");
    }

    /**
     * @param string $date
     * @return string
     */
    private function normalizeDate(string $date): string
    {
        $rusToEng = [
            'Янв' => 'Jan',
            'Фев' => 'Feb',
            'Мар' => 'Mar',
            'Апр' => 'Apr',
            'Май' => 'May',
            'Июн' => 'Jun',
            'Июл' => 'Jul',
            'Авг' => 'Aug',
            'Сен' => 'Sep',
            'Окт' => 'Oct',
            'Ноя' => 'Nov',
            'Дек' => 'Dec',
        ];

        $withEngMonths = strtr($date, $rusToEng);
        
        return trim(preg_replace('/[^a-z\d\s:\/]+/i', '', $withEngMonths));
    }
}
