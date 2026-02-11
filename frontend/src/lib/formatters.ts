/**
 * Format a number for display with locale-aware separators.
 * Returns an em-dash for null/undefined values.
 */
export function formatNumber(
  value: number | null | undefined,
  decimals = 2
): string {
  if (value === null || value === undefined) return "\u2014";
  return value.toLocaleString("en-US", {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  });
}

/** Format a number as currency (AED). */
export function formatAED(
  value: number | null | undefined,
  decimals = 2
): string {
  return formatNumber(value, decimals) + " AED";
}

/** Format a number as a percentage. */
export function formatPct(
  value: number | null | undefined,
  decimals = 2
): string {
  return formatNumber(value, decimals) + "%";
}
