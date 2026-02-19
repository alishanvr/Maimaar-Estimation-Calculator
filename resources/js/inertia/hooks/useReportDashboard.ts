import { useState, useEffect, useCallback } from 'react';
import type { ReportDashboardData, ReportFilters } from '../types/reports';
import { getReportDashboard } from '../lib/reports';

export function useReportDashboard(filters: ReportFilters) {
    const [data, setData] = useState<ReportDashboardData | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const fetch = useCallback(async () => {
        setIsLoading(true);
        setError(null);
        try {
            const result = await getReportDashboard(filters);
            setData(result);
        } catch {
            setError('Failed to load report data.');
        } finally {
            setIsLoading(false);
        }
        // Depend on individual primitive values to avoid object identity issues
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [
        filters.date_from,
        filters.date_to,
        // eslint-disable-next-line react-hooks/exhaustive-deps
        filters.statuses?.join(','),
        filters.customer_name,
        filters.salesperson_code,
    ]);

    useEffect(() => {
        fetch();
    }, [fetch]);

    return { data, isLoading, error, refetch: fetch };
}
