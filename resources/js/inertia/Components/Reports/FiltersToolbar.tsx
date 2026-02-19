import { useState, useRef, useEffect } from 'react';
import type { ReportFilters } from '../../types/reports';

interface FiltersToolbarProps {
    filters: ReportFilters;
    onChange: (filters: ReportFilters) => void;
    customers: string[];
    salespersons: string[];
}

const STATUS_OPTIONS = [
    { value: 'draft', label: 'Draft' },
    { value: 'calculated', label: 'Calculated' },
    { value: 'finalized', label: 'Finalized' },
];

export default function FiltersToolbar({
    filters,
    onChange,
    customers,
    salespersons,
}: FiltersToolbarProps) {
    const [statusOpen, setStatusOpen] = useState(false);
    const statusRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const handleClickOutside = (e: MouseEvent) => {
            if (
                statusRef.current &&
                !statusRef.current.contains(e.target as Node)
            ) {
                setStatusOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () =>
            document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const update = (partial: Partial<ReportFilters>) => {
        onChange({ ...filters, ...partial });
    };

    const toggleStatus = (status: string) => {
        const current = filters.statuses || [];
        const next = current.includes(status)
            ? current.filter((s) => s !== status)
            : [...current, status];
        update({ statuses: next.length > 0 ? next : undefined });
    };

    const hasFilters =
        filters.date_from ||
        filters.date_to ||
        (filters.statuses && filters.statuses.length > 0) ||
        filters.customer_name ||
        filters.salesperson_code;

    const clearFilters = () => {
        onChange({});
    };

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <div className="grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
                {/* Date From */}
                <div>
                    <label className="block text-xs font-medium text-gray-500 mb-1">
                        From
                    </label>
                    <input
                        type="date"
                        value={filters.date_from || ''}
                        onChange={(e) =>
                            update({
                                date_from: e.target.value || undefined,
                            })
                        }
                        className="w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:border-primary focus:ring-primary/20 focus:ring-2 outline-none"
                    />
                </div>

                {/* Date To */}
                <div>
                    <label className="block text-xs font-medium text-gray-500 mb-1">
                        To
                    </label>
                    <input
                        type="date"
                        value={filters.date_to || ''}
                        onChange={(e) =>
                            update({ date_to: e.target.value || undefined })
                        }
                        className="w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:border-primary focus:ring-primary/20 focus:ring-2 outline-none"
                    />
                </div>

                {/* Status Multi-select */}
                <div className="relative" ref={statusRef}>
                    <label className="block text-xs font-medium text-gray-500 mb-1">
                        Status
                    </label>
                    <button
                        onClick={() => setStatusOpen(!statusOpen)}
                        className="w-full text-sm text-left border border-gray-300 rounded-lg px-3 py-1.5 bg-white hover:bg-gray-50 transition flex items-center justify-between"
                    >
                        <span className="text-gray-700 truncate">
                            {filters.statuses?.length
                                ? filters.statuses.join(', ')
                                : 'All'}
                        </span>
                        <svg
                            className={`w-4 h-4 text-gray-400 transition ${statusOpen ? 'rotate-180' : ''}`}
                            fill="none"
                            stroke="currentColor"
                            strokeWidth={2}
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                            />
                        </svg>
                    </button>
                    {statusOpen && (
                        <div className="absolute top-full left-0 mt-1 z-50 w-full bg-white rounded-lg shadow-lg border border-gray-200 py-1">
                            {STATUS_OPTIONS.map((opt) => (
                                <label
                                    key={opt.value}
                                    className="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 cursor-pointer text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        checked={
                                            filters.statuses?.includes(
                                                opt.value,
                                            ) ?? false
                                        }
                                        onChange={() =>
                                            toggleStatus(opt.value)
                                        }
                                        className="rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                    {opt.label}
                                </label>
                            ))}
                        </div>
                    )}
                </div>

                {/* Customer */}
                <div>
                    <label className="block text-xs font-medium text-gray-500 mb-1">
                        Customer
                    </label>
                    <input
                        type="text"
                        list="customer-list"
                        value={filters.customer_name || ''}
                        onChange={(e) =>
                            update({
                                customer_name: e.target.value || undefined,
                            })
                        }
                        placeholder="Search..."
                        className="w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:border-primary focus:ring-primary/20 focus:ring-2 outline-none"
                    />
                    <datalist id="customer-list">
                        {customers.map((c) => (
                            <option key={c} value={c} />
                        ))}
                    </datalist>
                </div>

                {/* Salesperson */}
                <div>
                    <label className="block text-xs font-medium text-gray-500 mb-1">
                        Salesperson
                    </label>
                    <select
                        value={filters.salesperson_code || ''}
                        onChange={(e) =>
                            update({
                                salesperson_code:
                                    e.target.value || undefined,
                            })
                        }
                        className="w-full text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:border-primary focus:ring-primary/20 focus:ring-2 outline-none bg-white"
                    >
                        <option value="">All</option>
                        {salespersons.map((sp) => (
                            <option key={sp} value={sp}>
                                {sp}
                            </option>
                        ))}
                    </select>
                </div>

                {/* Clear */}
                <div>
                    {hasFilters && (
                        <button
                            onClick={clearFilters}
                            className="text-sm text-gray-500 hover:text-gray-700 transition px-3 py-1.5"
                        >
                            Clear filters
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
