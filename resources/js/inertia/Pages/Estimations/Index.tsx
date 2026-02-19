import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { useCurrency } from '../../hooks/useCurrency';
import { formatNumber } from '../../lib/formatters';
import { type Estimation, type EstimationStatus, type PaginationMeta } from '../../types';

interface Props {
    estimations: {
        data: Estimation[];
        meta: PaginationMeta;
        links: { prev: string | null; next: string | null };
    };
    filters: {
        status: string;
    };
}

const STATUS_FILTERS: { label: string; value: string }[] = [
    { label: 'All', value: '' },
    { label: 'Draft', value: 'draft' },
    { label: 'Calculated', value: 'calculated' },
    { label: 'Finalized', value: 'finalized' },
];

const STATUS_BADGE: Record<EstimationStatus, string> = {
    draft: 'bg-gray-100 text-gray-700',
    calculated: 'bg-green-100 text-green-700',
    finalized: 'bg-blue-100 text-blue-700',
};

export default function Index({ estimations, filters }: Props) {
    const { symbol, format } = useCurrency();
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const [isExporting, setIsExporting] = useState(false);

    const handleFilterChange = (status: string) => {
        router.get('/v2/estimations', { status: status || undefined }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleCreate = () => {
        router.post('/v2/estimations', {
            building_name: 'New Building',
            input_data: {},
        });
    };

    const handleDelete = (id: number) => {
        if (!confirm('Are you sure you want to delete this estimation?')) return;
        router.delete(`/v2/estimations/${id}`, { preserveScroll: true });
    };

    const handleClone = (id: number) => {
        router.post(`/v2/estimations/${id}/clone`);
    };

    const handleCompare = () => {
        const ids = Array.from(selectedIds);
        router.get('/v2/estimations/compare', { ids: ids.join(',') });
    };

    const handleBulkExport = async () => {
        setIsExporting(true);
        try {
            const { default: api } = await import('../../lib/api');
            const { downloadBlob } = await import('../../lib/download');
            const ids = Array.from(selectedIds);
            const { data } = await api.post('/estimations/bulk-export', {
                ids,
                sheets: ['recap', 'detail', 'fcpbs', 'sal', 'boq', 'jaf'],
            }, { responseType: 'blob' });
            downloadBlob(data, 'estimations-export.zip');
        } catch {
            alert('Failed to export estimations.');
        } finally {
            setIsExporting(false);
        }
    };

    const toggleSelection = (id: number) => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id); else next.add(id);
            return next;
        });
    };

    const toggleAll = () => {
        if (selectedIds.size === estimations.data.length) {
            setSelectedIds(new Set());
        } else {
            setSelectedIds(new Set(estimations.data.map((e) => e.id)));
        }
    };

    return (
        <AuthenticatedLayout title="Estimations">
            <div>
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h2 className="text-2xl font-bold text-gray-900">Estimations</h2>
                        <p className="text-gray-500 mt-1">Manage your construction estimations</p>
                    </div>
                    <button
                        onClick={handleCreate}
                        className="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/80 transition"
                    >
                        + New Estimation
                    </button>
                </div>

                {/* Status Filter Tabs */}
                <div className="flex gap-1 mb-6 bg-gray-100 rounded-lg p-1 w-fit">
                    {STATUS_FILTERS.map((f) => (
                        <button
                            key={f.value}
                            onClick={() => handleFilterChange(f.value)}
                            className={`px-4 py-1.5 text-sm rounded-md font-medium transition ${
                                filters.status === f.value
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            {f.label}
                        </button>
                    ))}
                </div>

                {/* Selection Toolbar */}
                {selectedIds.size > 0 && (
                    <div className="flex items-center gap-3 mb-4 bg-blue-50 border border-blue-200 rounded-lg px-4 py-2">
                        <span className="text-sm font-medium text-blue-700">{selectedIds.size} selected</span>
                        <div className="h-4 w-px bg-blue-200" />
                        <button onClick={handleCompare} disabled={selectedIds.size !== 2} className="text-sm font-medium text-blue-700 hover:text-blue-900 disabled:text-gray-400 disabled:cursor-not-allowed transition">Compare</button>
                        <button onClick={handleBulkExport} disabled={isExporting} className="text-sm font-medium text-blue-700 hover:text-blue-900 disabled:text-gray-400 transition">{isExporting ? 'Exporting...' : 'Export ZIP'}</button>
                        <div className="flex-1" />
                        <button onClick={() => setSelectedIds(new Set())} className="text-sm text-gray-500 hover:text-gray-700 transition">Clear</button>
                    </div>
                )}

                {/* Empty State */}
                {estimations.data.length === 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <h3 className="text-lg font-medium text-gray-900">No estimations yet</h3>
                        <p className="text-gray-500 mt-2 mb-4">Create your first estimation to get started.</p>
                        <button onClick={handleCreate} className="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/80 transition">
                            + New Estimation
                        </button>
                    </div>
                )}

                {/* Table */}
                {estimations.data.length > 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="bg-gray-50 border-b border-gray-200">
                                    <th className="w-10 px-3 py-3">
                                        <input type="checkbox" checked={estimations.data.length > 0 && selectedIds.size === estimations.data.length} onChange={toggleAll} className="rounded border-gray-300" />
                                    </th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-500">Quote #</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-500">Building</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-500">Customer</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                                    <th className="text-right px-4 py-3 font-medium text-gray-500">Weight (MT)</th>
                                    <th className="text-right px-4 py-3 font-medium text-gray-500">Price ({symbol})</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                                    <th className="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {estimations.data.map((est) => (
                                    <tr key={est.id} className={`hover:bg-gray-50 cursor-pointer transition ${selectedIds.has(est.id) ? 'bg-blue-50' : ''}`} onClick={() => router.visit(`/v2/estimations/${est.id}`)}>
                                        <td className="w-10 px-3 py-3">
                                            <input type="checkbox" checked={selectedIds.has(est.id)} onChange={(e) => { e.stopPropagation(); toggleSelection(est.id); }} onClick={(e) => e.stopPropagation()} className="rounded border-gray-300" />
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs">{est.quote_number || '\u2014'}</td>
                                        <td className="px-4 py-3 font-medium text-gray-900">{est.building_name || 'Untitled'}</td>
                                        <td className="px-4 py-3 text-gray-600">{est.customer_name || '\u2014'}</td>
                                        <td className="px-4 py-3">
                                            <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${STATUS_BADGE[est.status]}`}>{est.status}</span>
                                        </td>
                                        <td className="px-4 py-3 text-right font-mono text-xs">{formatNumber(est.total_weight_mt, 2)}</td>
                                        <td className="px-4 py-3 text-right font-mono text-xs">{format(est.total_price_aed, 0)}</td>
                                        <td className="px-4 py-3 text-gray-500 text-xs">{est.estimation_date || est.created_at?.slice(0, 10)}</td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <button onClick={(e) => { e.stopPropagation(); handleClone(est.id); }} className="text-primary hover:text-primary/80 text-xs font-medium transition">Clone</button>
                                                <button onClick={(e) => { e.stopPropagation(); handleDelete(est.id); }} className="text-red-500 hover:text-red-700 text-xs font-medium transition">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        {estimations.meta.last_page > 1 && (
                            <div className="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
                                <p className="text-sm text-gray-500">Showing {estimations.meta.from}&ndash;{estimations.meta.to} of {estimations.meta.total}</p>
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => router.get('/v2/estimations', { ...filters, page: estimations.meta.current_page - 1 }, { preserveState: true })}
                                        disabled={estimations.meta.current_page <= 1}
                                        className="px-3 py-1 text-sm rounded border border-gray-300 disabled:opacity-50 hover:bg-white transition"
                                    >Previous</button>
                                    <button
                                        onClick={() => router.get('/v2/estimations', { ...filters, page: estimations.meta.current_page + 1 }, { preserveState: true })}
                                        disabled={estimations.meta.current_page >= estimations.meta.last_page}
                                        className="px-3 py-1 text-sm rounded border border-gray-300 disabled:opacity-50 hover:bg-white transition"
                                    >Next</button>
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
