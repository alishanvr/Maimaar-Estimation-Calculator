import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { useCurrency } from '../../hooks/useCurrency';
import { formatNumber } from '../../lib/formatters';
import { type Project, type ProjectStatus, type PaginationMeta } from '../../types';

interface Props {
    projects: {
        data: Project[];
        meta: PaginationMeta;
    };
    filters: {
        status: string;
        search: string;
    };
}

const STATUS_FILTERS: { label: string; value: string }[] = [
    { label: 'All', value: '' },
    { label: 'Draft', value: 'draft' },
    { label: 'In Progress', value: 'in_progress' },
    { label: 'Completed', value: 'completed' },
    { label: 'Archived', value: 'archived' },
];

const STATUS_BADGE: Record<ProjectStatus, string> = {
    draft: 'bg-gray-100 text-gray-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-green-100 text-green-700',
    archived: 'bg-blue-100 text-blue-700',
};

export default function Index({ projects, filters }: Props) {
    const { symbol, format: formatCur } = useCurrency();
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [searchValue, setSearchValue] = useState(filters.search);

    const createForm = useForm({
        project_number: '',
        project_name: '',
        customer_name: '',
        location: '',
    });

    const handleFilterChange = (status: string) => {
        router.get('/v2/projects', { status: status || undefined, search: filters.search || undefined }, { preserveState: true, preserveScroll: true });
    };

    const handleSearch = () => {
        router.get('/v2/projects', { status: filters.status || undefined, search: searchValue || undefined }, { preserveState: true, preserveScroll: true });
    };

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/v2/projects', {
            onSuccess: () => {
                setShowCreateDialog(false);
                createForm.reset();
            },
        });
    };

    const handleDelete = (id: number) => {
        if (!confirm('Are you sure you want to delete this project?')) return;
        router.delete(`/v2/projects/${id}`, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout title="Projects">
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h2 className="text-2xl font-bold text-gray-900">Projects</h2>
                        <p className="text-gray-500 mt-1">Manage multi-building projects</p>
                    </div>
                    <button onClick={() => setShowCreateDialog(true)} className="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/80 transition">
                        + New Project
                    </button>
                </div>

                {/* Search */}
                <div className="mb-4">
                    <input
                        type="text"
                        placeholder="Search by name, number, or customer..."
                        value={searchValue}
                        onChange={(e) => setSearchValue(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                        onBlur={handleSearch}
                        className="w-full max-w-md px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                    />
                </div>

                {/* Status Filter Tabs */}
                <div className="flex gap-1 mb-6 bg-gray-100 rounded-lg p-1 w-fit">
                    {STATUS_FILTERS.map((f) => (
                        <button key={f.value} onClick={() => handleFilterChange(f.value)} className={`px-4 py-1.5 text-sm rounded-md font-medium transition ${filters.status === f.value ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}>
                            {f.label}
                        </button>
                    ))}
                </div>

                {/* Empty State */}
                {projects.data.length === 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <h3 className="text-lg font-medium text-gray-900">No projects yet</h3>
                        <p className="text-gray-500 mt-2 mb-4">Create your first project to group multiple buildings.</p>
                        <button onClick={() => setShowCreateDialog(true)} className="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/80 transition">+ New Project</button>
                    </div>
                )}

                {/* Table */}
                {projects.data.length > 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="bg-gray-50 border-b border-gray-200">
                                    <th className="text-left px-4 py-3 font-medium text-gray-500">Project #</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-500">Name</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-500">Customer</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                                    <th className="text-center px-4 py-3 font-medium text-gray-500">Buildings</th>
                                    <th className="text-right px-4 py-3 font-medium text-gray-500">Weight (MT)</th>
                                    <th className="text-right px-4 py-3 font-medium text-gray-500">Price ({symbol})</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                                    <th className="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {projects.data.map((proj) => (
                                    <tr key={proj.id} className="hover:bg-gray-50 cursor-pointer transition" onClick={() => router.visit(`/v2/projects/${proj.id}`)}>
                                        <td className="px-4 py-3 font-mono text-xs">{proj.project_number}</td>
                                        <td className="px-4 py-3 font-medium text-gray-900">{proj.project_name}</td>
                                        <td className="px-4 py-3 text-gray-600">{proj.customer_name || '\u2014'}</td>
                                        <td className="px-4 py-3">
                                            <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${STATUS_BADGE[proj.status]}`}>{proj.status.replace('_', ' ')}</span>
                                        </td>
                                        <td className="px-4 py-3 text-center font-mono text-xs">{proj.summary?.building_count ?? 0}</td>
                                        <td className="px-4 py-3 text-right font-mono text-xs">{formatNumber(proj.summary?.total_weight ?? null, 2)}</td>
                                        <td className="px-4 py-3 text-right font-mono text-xs">{formatCur(proj.summary?.total_price ?? null, 0)}</td>
                                        <td className="px-4 py-3 text-gray-500 text-xs">{proj.created_at?.slice(0, 10)}</td>
                                        <td className="px-4 py-3 text-right">
                                            <button onClick={(e) => { e.stopPropagation(); handleDelete(proj.id); }} className="text-red-500 hover:text-red-700 text-xs font-medium transition">Delete</button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {projects.meta.last_page > 1 && (
                            <div className="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
                                <p className="text-sm text-gray-500">Showing {projects.meta.from}&ndash;{projects.meta.to} of {projects.meta.total}</p>
                                <div className="flex gap-2">
                                    <button onClick={() => router.get('/v2/projects', { ...filters, page: projects.meta.current_page - 1 }, { preserveState: true })} disabled={projects.meta.current_page <= 1} className="px-3 py-1 text-sm rounded border border-gray-300 disabled:opacity-50 hover:bg-white transition">Previous</button>
                                    <button onClick={() => router.get('/v2/projects', { ...filters, page: projects.meta.current_page + 1 }, { preserveState: true })} disabled={projects.meta.current_page >= projects.meta.last_page} className="px-3 py-1 text-sm rounded border border-gray-300 disabled:opacity-50 hover:bg-white transition">Next</button>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Create Dialog */}
                {showCreateDialog && (
                    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                        <div className="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">New Project</h3>
                            <form onSubmit={handleCreate} className="space-y-3">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Project Number *</label>
                                    <input type="text" value={createForm.data.project_number} onChange={(e) => createForm.setData('project_number', e.target.value)} placeholder="PRJ-001" className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" />
                                    {createForm.errors.project_number && <p className="mt-1 text-xs text-red-600">{createForm.errors.project_number}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Project Name *</label>
                                    <input type="text" value={createForm.data.project_name} onChange={(e) => createForm.setData('project_name', e.target.value)} placeholder="Warehouse Complex" className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" />
                                    {createForm.errors.project_name && <p className="mt-1 text-xs text-red-600">{createForm.errors.project_name}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Customer Name</label>
                                    <input type="text" value={createForm.data.customer_name} onChange={(e) => createForm.setData('customer_name', e.target.value)} placeholder="Acme Corp" className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                    <input type="text" value={createForm.data.location} onChange={(e) => createForm.setData('location', e.target.value)} placeholder="Dubai, UAE" className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" />
                                </div>
                                <div className="flex justify-end gap-3 mt-6">
                                    <button type="button" onClick={() => setShowCreateDialog(false)} className="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Cancel</button>
                                    <button type="submit" disabled={createForm.processing || !createForm.data.project_number || !createForm.data.project_name} className="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary/80 transition disabled:opacity-50">
                                        {createForm.processing ? 'Creating...' : 'Create Project'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
