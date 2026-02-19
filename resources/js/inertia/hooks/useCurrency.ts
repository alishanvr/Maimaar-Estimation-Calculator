import { usePage } from '@inertiajs/react';
import { type PageProps } from '../types';
import { formatNumber, formatCurrency, formatCurrencyPerMT } from '../lib/formatters';

export function useCurrency() {
    const { appSettings } = usePage<PageProps>().props;
    const { currency_symbol: symbol, exchange_rate: rate } = appSettings;

    return {
        symbol,
        rate,
        convert: (aedValue: number | null | undefined): number | null => {
            if (aedValue === null || aedValue === undefined) return null;
            return aedValue * rate;
        },
        format: (aedValue: number | null | undefined, decimals = 2): string => {
            return formatCurrency(aedValue, rate, symbol, decimals);
        },
        formatPerMT: (aedValue: number | null | undefined, decimals = 2): string => {
            return formatCurrencyPerMT(aedValue, rate, symbol, decimals);
        },
        formatNumber,
    };
}
