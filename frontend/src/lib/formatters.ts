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

/** Format a number as currency (AED). Kept for backward compatibility. */
export function formatAED(
  value: number | null | undefined,
  decimals = 2
): string {
  return formatNumber(value, decimals) + " AED";
}

/** Format an AED value in the given display currency. */
export function formatCurrency(
  value: number | null | undefined,
  exchangeRate: number,
  currencySymbol: string,
  decimals = 2
): string {
  if (value === null || value === undefined) return "\u2014";
  const converted = value * exchangeRate;
  return formatNumber(converted, decimals) + " " + currencySymbol;
}

/** Format an AED value as price-per-MT in the given display currency. */
export function formatCurrencyPerMT(
  value: number | null | undefined,
  exchangeRate: number,
  currencySymbol: string,
  decimals = 2
): string {
  if (value === null || value === undefined) return "\u2014";
  const converted = value * exchangeRate;
  return formatNumber(converted, decimals) + " " + currencySymbol + "/MT";
}

/** Format a number as a percentage. */
export function formatPct(
  value: number | null | undefined,
  decimals = 2
): string {
  return formatNumber(value, decimals) + "%";
}
