<?php

namespace Storeman;

abstract class FilesystemUtility
{
    /**
     * Does the same thing as its php core counterpart but returns atime, ctime and mtime in full precision.
     *
     * @param string $path
     * @return array
     * @throws \RuntimeException
     */
    public static function lstat(string $path): array
    {
        exec('stat ' . escapeshellarg('-c%D;%i;%f;%h;%u;%g;%t;%s;%x;%y;%z;%B;%b') . ' ' . escapeshellarg($path) . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0)
        {
            throw new \RuntimeException();
        }

        $array = str_getcsv($output[0], ';');

        $stat = [
            'dev' => hexdec($array[0]),
            'ino' => (int)$array[1],
            'mode' => hexdec($array[2]),
            'nlink' => (int)$array[3],
            'uid' => (int)$array[4],
            'gid' => (int)$array[5],
            'rdev' => hexdec($array[6]),
            'size' => (int)$array[7],
            'atime' => static::parseTime($array[8]),
            'mtime' => static::parseTime($array[9]),
            'ctime' => static::parseTime($array[10]),
            'blksize' => (int)$array[11],
            'blocks' => (int)$array[12],
        ];

        // for full compatibility
        $stat[0] = $stat['dev'];
        $stat[1] = $stat['ino'];
        $stat[2] = $stat['mode'];
        $stat[3] = $stat['nlink'];
        $stat[4] = $stat['uid'];
        $stat[5] = $stat['gid'];
        $stat[6] = $stat['rdev'];
        $stat[7] = $stat['size'];
        $stat[8] = $stat['atime'];
        $stat[9] = $stat['mtime'];
        $stat[10] = $stat['ctime'];
        $stat[11] = $stat['blksize'];
        $stat[12] = $stat['blocks'];

        return $stat;
    }

    /**
     * Parses strings like this: 2018-07-11 00:40:23.636828641 +0200
     * Returns unix timestamp with sub-second resolution as float.
     *
     * @param string $timeString
     * @return float
     */
    public static function parseTime(string $timeString): float
    {
        $fractionStringEnd = strpos($timeString, ' ', 19);
        $reducedPrecisionString = substr($timeString, 0, 19) . substr($timeString, $fractionStringEnd);

        $time = (float)\DateTime::createFromFormat('Y-m-d H:i:s O', $reducedPrecisionString)->getTimestamp();
        $time += (float)substr($timeString, 19, $fractionStringEnd - 19);

        return $time;
    }

    /**
     * Rebuilds time string like this from given unixtime: 2018-07-11 00:40:23.636828641 +0200
     *
     * @param float $timestamp
     * @param int $decimals
     * @param \DateTimeZone $timeZone
     * @return string
     */
    public static function buildTime(float $timestamp, int $decimals = 9, \DateTimeZone $timeZone = null): string
    {
        $dateTime = \DateTime::createFromFormat('U', (int)$timestamp);
        $timestampString = sprintf("%.{$decimals}f", $timestamp);

        if ($timeZone)
        {
            $dateTime->setTimezone($timeZone);
        }

        $string = $dateTime->format('Y-m-d H:i:s');
        $string .= substr($timestampString, strpos($timestampString, '.'));
        $string .= " {$dateTime->format('O')}";

        return $string;
    }
}
