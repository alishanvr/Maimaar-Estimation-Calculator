import { useBranding } from "@/contexts/BrandingContext";
import { formatCurrency, formatCurrencyPerMT } from "@/lib/formatters";

/**
 * Convenience hook for currency conversion and formatting.
 * Wraps BrandingContext currency fields with helper methods.
 */
export function useCurrency() {
  const { branding } = useBranding();
  const { display_currency: currency, currency_symbol: symbol, exchange_rate: rate } = branding;

  return {
    /** ISO currency code, e.g. "USD" */
    currency,
    /** Display symbol/code, e.g. "USD" */
    symbol,
    /** Exchange rate from AED */
    rate,
    /** Convert an AED value to display currency */
    convert: (aedValue: number) => aedValue * rate,
    /** Format an AED value in display currency, e.g. "1,234.56 USD" */
    format: (aedValue: number | null | undefined, decimals = 2) =>
      formatCurrency(aedValue, rate, symbol, decimals),
    /** Format an AED value as price-per-MT, e.g. "1,234.56 USD/MT" */
    formatPerMT: (aedValue: number | null | undefined, decimals = 2) =>
      formatCurrencyPerMT(aedValue, rate, symbol, decimals),
    /** Build a label with the currency, e.g. "Price (USD)" */
    label: (prefix: string) => `${prefix} (${symbol})`,
  };
}
