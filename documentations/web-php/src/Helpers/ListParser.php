<?php
/**
 * QuickEst - List Parser Helper
 *
 * Parses building dimension strings like "2@24,3@18" into structured arrays
 * Replicates VBA functions: GetList() and ExpList()
 */

namespace QuickEst\Helpers;

class ListParser {

    /**
     * Parse a dimension string into count/value pairs
     * Input: "2@24,3@18" or "24+18+18+18" or "2x24,3x18"
     * Output: [[count => 2, value => 24], [count => 3, value => 18]]
     *
     * Replicates VBA GetList() function
     */
    public static function parseList(string $text): array {
        $result = [];

        // Normalize separators
        $text = self::normalizeSeparators($text);

        // Split by comma
        $parts = explode(',', $text);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            // Check for count@value or countxvalue format
            if (strpos($part, '@') !== false) {
                list($count, $value) = explode('@', $part, 2);
                $result[] = [
                    'count' => (int) trim($count),
                    'value' => (float) trim($value)
                ];
            } else {
                // Single value, count is 1
                $result[] = [
                    'count' => 1,
                    'value' => (float) $part
                ];
            }
        }

        return $result;
    }

    /**
     * Expand a parsed list into individual values
     * Input: [[count => 2, value => 24], [count => 3, value => 18]]
     * Output: [24, 24, 18, 18, 18]
     *
     * Replicates VBA ExpList() function
     */
    public static function expandList(array $list): array {
        $result = [];

        foreach ($list as $item) {
            for ($i = 0; $i < $item['count']; $i++) {
                $result[] = $item['value'];
            }
        }

        return $result;
    }

    /**
     * Get total count from parsed list
     */
    public static function getTotalCount(array $list): int {
        $total = 0;
        foreach ($list as $item) {
            $total += $item['count'];
        }
        return $total;
    }

    /**
     * Get total sum (count * value) from parsed list
     */
    public static function getTotalSum(array $list): float {
        $total = 0;
        foreach ($list as $item) {
            $total += $item['count'] * $item['value'];
        }
        return $total;
    }

    /**
     * Normalize various separator formats to standard format
     * Replicates VBA FixSep() function
     */
    private static function normalizeSeparators(string $text): string {
        // Replace various separators with comma
        $text = str_replace(['+', ';', '/', '\\', "'", '&'], ',', $text);

        // Replace 'x' or 'X' with '@' (for dimension format)
        $text = str_ireplace('x', '@', $text);

        // Replace ':' with '@'
        $text = str_replace(':', '@', $text);

        return $text;
    }

    /**
     * Parse slope string which may include slope values
     * Input: "2@24@0.1" means 2 spans of 24m with 0.1 slope
     * Output: [[count => 2, value => 24, slope => 0.1]]
     */
    public static function parseSlopeList(string $text): array {
        $result = [];

        $text = self::normalizeSeparators($text);
        $parts = explode(',', $text);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            $segments = explode('@', $part);

            if (count($segments) >= 3) {
                $result[] = [
                    'count' => (int) trim($segments[0]),
                    'value' => (float) trim($segments[1]),
                    'slope' => (float) trim($segments[2])
                ];
            } elseif (count($segments) == 2) {
                $result[] = [
                    'count' => (int) trim($segments[0]),
                    'value' => (float) trim($segments[1]),
                    'slope' => 0
                ];
            } else {
                $result[] = [
                    'count' => 1,
                    'value' => (float) $segments[0],
                    'slope' => 0
                ];
            }
        }

        return $result;
    }

    /**
     * Calculate building width from spans
     */
    public static function calculateWidth(string $spansText): float {
        $spans = self::parseList($spansText);
        return self::getTotalSum($spans);
    }

    /**
     * Calculate building length from bays
     */
    public static function calculateLength(string $baysText): float {
        $bays = self::parseList($baysText);
        return self::getTotalSum($bays);
    }
}
