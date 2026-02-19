import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import { Link, router, useForm } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { useCurrency } from '../../hooks/useCurrency';
import { formatNumber } from '../../lib/formatters';
import { type Project, type Estimation, type EstimationStatus, type ActivityLogEntry } from '../../types';

interface Props {
    project: Project & { estimations: Estimation[] };
}

const STATUS_BADGE: Record<EstimationStatus, string> = {
    draft: 'bg-gray-100 text-gray-700',
    calculated: 'bg-green-100 text-green-700',
    finalized: 'bg-blue-100 text-blue-700',
};

const PROJECT_STATUSES = ['draft', 'in_progress', 'completed', 'archived'];

export default function Show({ project }: Props) {
    const { symbol, format: formatCur } = useCurrency();
    const [activeTab, setActiveTab] = useState<'buildings' | 'history'>('buildings');
    const [isEditing, setIsEditing] = useState(false);
    const [history, setHistory] = useState<ActivityLogEntry[]>([]);
    const [loadingHistory, setLoadingHistory] = useState(false);

    const editForm = useForm({
        project_name: project.project_name,
        customer_name: project.customer_name ?? '',
        location: project.location ?? '',
        description: project.description ?? '',
        status: project.status,
    });

    const handleSave = (e: React.FormEvent) => {
        e.preventDefault();
        editForm.put(`/v2/projects/${project.id}`, {
            preserveScroll: true,
            onSuccess: () => setIsEditing(false),
        });
    };

    const handleAddBuilding = () => {
        router.post(`/v2/projects/${project.id}/buildings`);
    };

    const handleDuplicate = (estimationId: number) => {
        router.post(`/v2/projects/${project.id}/buildings/${estimationId}/duplicate`, {}, { preserveScroll: true });
    };

    const handleRemove = (estimationId: number) => {
        if (!confirm('Remove this building from the project?')) return;
        router.delete(`/v2/projects/${project.id}/buildings/${estimationId}`, { preserveScroll: true });
    };

    const loadHistory = async () => {
        if (history.length > 0) return;
        setLoadingHistory(true);
        try {
            const axios = (await import('axios')).default;
            const { data } = await axios.get(`/v2/projects/${project.id}/history`, {
                headers: { Accept: 'application/json' },
            });
            setHistory(data.data || []);
        } catch {
            // Silently fail
        } finally {
            setLoadingHistory(false);
        }
    };

    useEffect(() => {
        if (activeTab === 'history') loadHistory();
    }, [activeTab]);

    return (
        <AuthenticatedLayout title={project.project_name}>
            <div>
                <Link href="/v2/projects" className="text-primary hover:text-primary/80 text-sm font-medium">
                    &larr; Back to Projects
                </Link>

                {/* Project Header */}
                <div className="mt-4 mb-6">
                    {!isEditing ? (
                        <div className="flex items-start justify-between">
                            <div>
                                <div className="flex items-center gap-3">
                                    <h2 className="text-2xl font-bold text-gray-900">{project.project_name}</h2>
                                    <span className="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{project.status.replace('_', ' ')}</span>
                                </div>
                                <p className="text-sm text-gray-500 mt-1">
                                    {project.project_number} {project.customer_name && `\u00B7 ${project.customer_name}`} {project.location && `\u00B7 ${project.location}`}
                                </p>
                                {project.description && <p className="text-sm text-gray-600 mt-2">{project.description}</p>}
                            </div>
                            <button onClick={() => setIsEditing(true)} className="text-sm text-primary hover:text-primary/80 font-medium">Edit</button>
                        </div>
                    ) : (
                        <form onSubmit={handleSave} className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 space-y-3">
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-500 mb-1">Project Name</label>
                                    <input type="text" value={editForm.data.project_name} onChange={(e) => editForm.setData('project_name', e.target.value)} className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-500 mb-1">Customer</label>
                                    <input type="text" value={editForm.data.customer_name} onChange={(e) => editForm.setData('customer_name', e.target.value)} className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-500 mb-1">Location</label>
                                    <input type="text" value={editForm.data.location} onChange={(e) => editForm.setData('location', e.target.value)} className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-500 mb-1">Status</label>
                                    <select value={editForm.data.status} onChange={(e) => editForm.setData('status', e.target.value as Project['status'])} className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                                        {PROJECT_STATUSES.map((s) => <option key={s} value={s}>{s.replace('_', ' ')}</option>)}
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-500 mb-1">Description</label>
                                <textarea value={editForm.data.description} onChange={(e) => editForm.setData('description', e.target.value)} rows={2} className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" />
                            </div>
                            <div className="flex gap-2 justify-end">
                                <button type="button" onClick={() => setIsEditing(false)} className="px-3 py-1.5 text-sm text-gray-600">Cancel</button>
                                <button type="submit" disabled={editForm.processing} className="bg-primary text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-primary/80 transition disabled:opacity-50">{editForm.processing ? 'Saving...' : 'Save'}</button>
                            </div>
                        </form>
                    )}
                </div>

                {/* Summary */}
                <div className="grid grid-cols-3 gap-4 mb-6">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                        <p className="text-sm text-gray-500">Buildings</p>
                        <p className="text-2xl font-bold text-gray-900">{project.summary?.building_count ?? 0}</p>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                        <p className="text-sm text-gray-500">Total Weight (MT)</p>
                        <p className="text-2xl font-bold text-gray-900">{formatNumber(project.summary?.total_weight ?? null, 2)}</p>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                        <p className="text-sm text-gray-500">Total Price ({symbol})</p>
                        <p className="text-2xl font-bold text-gray-900">{formatCur(project.summary?.total_price ?? null, 0)}</p>
                    </div>
                </div>

                {/* Tabs */}
                <div className="flex gap-1 mb-4 bg-gray-100 rounded-lg p-1 w-fit">
                    <button onClick={() => setActiveTab('buildings')} className={`px-4 py-1.5 text-sm rounded-md font-medium transition ${activeTab === 'buildings' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'}`}>Buildings</button>
                    <button onClick={() => setActiveTab('history')} className={`px-4 py-1.5 text-sm rounded-md font-medium transition ${activeTab === 'history' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'}`}>History</button>
                </div>

                {/* Buildings Tab */}
                {activeTab === 'buildings' && (
                    <div>
                        <div className="flex justify-end mb-3">
                            <button onClick={handleAddBuilding} className="bg-primary text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-primary/80 transition">+ Add Building</button>
                        </div>
                        {(!project.estimations || project.estimations.length === 0) ? (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                                <p className="text-gray-500">No buildings in this project yet.</p>
                            </div>
                        ) : (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="bg-gray-50 border-b border-gray-200">
                                            <th className="text-left px-4 py-3 font-medium text-gray-500">Building</th>
                                            <th className="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                                            <th className="text-right px-4 py-3 font-medium text-gray-500">Weight (MT)</th>
                                            <th className="text-right px-4 py-3 font-medium text-gray-500">Price ({symbol})</th>
                                            <th className="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {project.estimations.map((est) => (
                                            <tr key={est.id} className="hover:bg-gray-50 cursor-pointer transition" onClick={() => router.visit(`/v2/estimations/${est.id}`)}>
                                                <td className="px-4 py-3 font-medium text-gray-900">{est.building_name || 'Untitled'}</td>
                                                <td className="px-4 py-3"><span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${STATUS_BADGE[est.status]}`}>{est.status}</span></td>
                                                <td className="px-4 py-3 text-right font-mono text-xs">{formatNumber(est.total_weight_mt, 2)}</td>
                                                <td className="px-4 py-3 text-right font-mono text-xs">{formatCur(est.total_price_aed, 0)}</td>
                                                <td className="px-4 py-3 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button onClick={(e) => { e.stopPropagation(); handleDuplicate(est.id); }} className="text-primary hover:text-primary/80 text-xs font-medium transition">Duplicate</button>
                                                        <button onClick={(e) => { e.stopPropagation(); handleRemove(est.id); }} className="text-red-500 hover:text-red-700 text-xs font-medium transition">Remove</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}

                {/* History Tab */}
                {activeTab === 'history' && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200">
                        {loadingHistory ? (
                            <div className="p-8 text-center text-gray-400">Loading history...</div>
                        ) : history.length === 0 ? (
                            <div className="p-8 text-center text-gray-500">No activity recorded yet.</div>
                        ) : (
                            <div className="divide-y divide-gray-100">
                                {history.map((entry) => (
                                    <div key={entry.id} className="px-4 py-3">
                                        <p className="text-sm text-gray-900">{entry.description}</p>
                                        <p className="text-xs text-gray-500 mt-1">
                                            {entry.causer_name && <span>{entry.causer_name} &middot; </span>}
                                            {new Date(entry.created_at).toLocaleString()}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
