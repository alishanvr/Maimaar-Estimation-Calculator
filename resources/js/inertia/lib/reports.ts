import api from './api';
import type { ReportDashboardData, ReportFilters } from '../types/reports';

function buildFilterParams(
    filters?: ReportFilters,
): Record<string, string | string[]> {
    const params: Record<string, string | string[]> = {};
    if (!filters) return params;

    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;
    if (filters.statuses?.length) params['statuses[]'] = filters.statuses;
    if (filters.customer_name) params.customer_name = filters.customer_name;
    if (filters.salesperson_code)
        params.salesperson_code = filters.salesperson_code;

    return params;
}

export async function getReportDashboard(
    filters?: ReportFilters,
): Promise<ReportDashboardData> {
    const params = buildFilterParams(filters);
    const { data } = await api.get('/reports/dashboard', { params });
    return data.data;
}

export async function exportReportCsv(
    filters?: ReportFilters,
): Promise<Blob> {
    const params = buildFilterParams(filters);
    const { data } = await api.get('/reports/export/csv', {
        params,
        responseType: 'blob',
    });
    return data;
}

export async function exportReportPdf(
    filters?: ReportFilters,
): Promise<Blob> {
    const params = buildFilterParams(filters);
    const { data } = await api.get('/reports/export/pdf', {
        params,
        responseType: 'blob',
    });
    return data;
}
