import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import { Link } from '@inertiajs/react';
import { useCurrency } from '../../hooks/useCurrency';
import { formatNumber } from '../../lib/formatters';
import { type ComparisonEstimation, type EstimationStatus } from '../../types';

interface Props {
    estimations: ComparisonEstimation[];
}

const STATUS_BADGE: Record<EstimationStatus, string> = {
    draft: 'bg-gray-100 text-gray-700',
    calculated: 'bg-green-100 text-green-700',
    finalized: 'bg-blue-100 text-blue-700',
};

export default function Compare({ estimations }: Props) {
    const { format, symbol } = useCurrency();
    const [a, b] = estimations;

    if (!a || !b) {
        return (
            <AuthenticatedLayout title="Compare Estimations">
                <div className="text-center py-12">
                    <p className="text-gray-500">Invalid comparison. Please select exactly 2 estimations.</p>
                    <Link href="/v2/estimations" className="text-primary hover:text-primary/80 text-sm font-medium mt-4 inline-block">
                        &larr; Back to Estimations
                    </Link>
                </div>
            </AuthenticatedLayout>
        );
    }

    const metrics = [
        { label: 'Total Weight (MT)', key: 'total_weight_mt', format: (v: number | null) => formatNumber(v, 2) },
        { label: `Total Price (${symbol})`, key: 'total_price_aed', format: (v: number | null) => format(v, 0) },
        { label: `Price/MT (${symbol})`, key: 'price_per_mt', format: (v: number | null) => format(v, 2) },
        { label: `FOB Price (${symbol})`, key: 'fob_price_aed', format: (v: number | null) => format(v, 0) },
        { label: 'Steel Weight (kg)', key: 'steel_weight_kg', format: (v: number | null) => formatNumber(v, 0) },
        { label: 'Panels Weight (kg)', key: 'panels_weight_kg', format: (v: number | null) => formatNumber(v, 0) },
    ];

    const getMetricValue = (est: ComparisonEstimation, key: string): number | null => {
        if (key === 'total_weight_mt') return est.total_weight_mt;
        if (key === 'total_price_aed') return est.total_price_aed;
        if (est.summary && key in est.summary) {
            return (est.summary as Record<string, number>)[key] ?? null;
        }
        return null;
    };

    // Find differing input fields
    const inputDiffs: { field: string; valueA: unknown; valueB: unknown }[] = [];
    const allKeys = new Set([...Object.keys(a.input_data || {}), ...Object.keys(b.input_data || {})]);
    for (const key of allKeys) {
        if (key === 'openings' || key === 'accessories' || key === 'cranes' || key === 'mezzanines' || key === 'partitions' || key === 'canopies' || key === 'liners') continue;
        const valA = a.input_data?.[key];
        const valB = b.input_data?.[key];
        if (JSON.stringify(valA) !== JSON.stringify(valB)) {
            inputDiffs.push({ field: key, valueA: valA, valueB: valB });
        }
    }

    return (
        <AuthenticatedLayout title="Compare Estimations">
            <div className="mb-6">
                <Link href="/v2/estimations" className="text-primary hover:text-primary/80 text-sm font-medium">
                    &larr; Back to Estimations
                </Link>
                <h2 className="text-2xl font-bold text-gray-900 mt-2">Compare Estimations</h2>
            </div>

            {/* Header cards */}
            <div className="grid grid-cols-2 gap-6 mb-6">
                {[a, b].map((est) => (
                    <div key={est.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <div className="flex items-center gap-2 mb-1">
                            <span className="font-mono text-xs text-gray-500">{est.quote_number || '\u2014'}</span>
                            <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${STATUS_BADGE[est.status]}`}>
                                {est.status}
                            </span>
                        </div>
                        <p className="font-medium text-gray-900">{est.building_name || 'Untitled'}</p>
                        <p className="text-xs text-gray-500">Rev: {est.revision_no || '0'}</p>
                    </div>
                ))}
            </div>

            {/* Metrics Table */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="bg-gray-50 border-b border-gray-200">
                            <th className="text-left px-4 py-3 font-medium text-gray-500">Metric</th>
                            <th className="text-right px-4 py-3 font-medium text-gray-500">{a.building_name || 'Est A'}</th>
                            <th className="text-right px-4 py-3 font-medium text-gray-500">{b.building_name || 'Est B'}</th>
                            <th className="text-right px-4 py-3 font-medium text-gray-500">Delta</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {metrics.map((m) => {
                            const valA = getMetricValue(a, m.key);
                            const valB = getMetricValue(b, m.key);
                            const delta = valA !== null && valB !== null ? valB - valA : null;
                            const pct = valA && delta !== null ? ((delta / valA) * 100) : null;
                            return (
                                <tr key={m.key}>
                                    <td className="px-4 py-3 font-medium text-gray-900">{m.label}</td>
                                    <td className="px-4 py-3 text-right font-mono text-xs">{m.format(valA)}</td>
                                    <td className="px-4 py-3 text-right font-mono text-xs">{m.format(valB)}</td>
                                    <td className="px-4 py-3 text-right font-mono text-xs">
                                        {delta !== null && (
                                            <span className={delta > 0 ? 'text-red-600' : delta < 0 ? 'text-green-600' : 'text-gray-500'}>
                                                {delta > 0 ? '+' : ''}{formatNumber(delta, 2)}
                                                {pct !== null && <span className="text-gray-400 ml-1">({pct > 0 ? '+' : ''}{pct.toFixed(1)}%)</span>}
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            {/* Input Differences */}
            {inputDiffs.length > 0 && (
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div className="px-4 py-3 bg-gray-50 border-b border-gray-200">
                        <h3 className="font-medium text-gray-900">Input Differences ({inputDiffs.length})</h3>
                    </div>
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-gray-200">
                                <th className="text-left px-4 py-2 font-medium text-gray-500">Field</th>
                                <th className="text-left px-4 py-2 font-medium text-gray-500">{a.building_name || 'Est A'}</th>
                                <th className="text-left px-4 py-2 font-medium text-gray-500">{b.building_name || 'Est B'}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {inputDiffs.map((d) => (
                                <tr key={d.field}>
                                    <td className="px-4 py-2 font-mono text-xs text-gray-700">{d.field}</td>
                                    <td className="px-4 py-2 text-xs">{String(d.valueA ?? '\u2014')}</td>
                                    <td className="px-4 py-2 text-xs">{String(d.valueB ?? '\u2014')}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
