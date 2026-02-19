export function formatNumber(
    value: number | null | undefined,
    decimals = 2,
): string {
    if (value === null || value === undefined) return '\u2014';
    return value.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

export function formatAED(
    value: number | null | undefined,
    decimals = 2,
): string {
    return formatNumber(value, decimals) + ' AED';
}

export function formatCurrency(
    value: number | null | undefined,
    exchangeRate: number,
    currencySymbol: string,
    decimals = 2,
): string {
    if (value === null || value === undefined) return '\u2014';
    const converted = value * exchangeRate;
    return formatNumber(converted, decimals) + ' ' + currencySymbol;
}

export function formatCurrencyPerMT(
    value: number | null | undefined,
    exchangeRate: number,
    currencySymbol: string,
    decimals = 2,
): string {
    if (value === null || value === undefined) return '\u2014';
    const converted = value * exchangeRate;
    return formatNumber(converted, decimals) + ' ' + currencySymbol + '/MT';
}

export function formatPct(
    value: number | null | undefined,
    decimals = 2,
): string {
    return formatNumber(value, decimals) + '%';
}
