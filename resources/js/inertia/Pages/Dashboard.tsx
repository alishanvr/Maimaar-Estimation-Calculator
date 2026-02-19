import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';
import { Link, usePage } from '@inertiajs/react';
import { type PageProps } from '../types';

interface Props {
    stats: {
        total: number;
        draft: number;
        calculated: number;
        finalized: number;
    };
}

export default function Dashboard({ stats }: Props) {
    const { auth, appSettings } = usePage<PageProps>().props;

    return (
        <AuthenticatedLayout title="Dashboard">
            <div className="mb-8">
                <h2 className="text-2xl font-bold text-gray-900">
                    Welcome back, {auth.user?.name}
                </h2>
                <p className="text-gray-500 mt-1">
                    {appSettings?.app_name ?? 'Maimaar'} Dashboard
                </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="text-sm font-medium text-gray-500">Total Estimations</h3>
                    <p className="mt-2 text-3xl font-bold text-gray-900">{stats.total}</p>
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="text-sm font-medium text-gray-500">Draft</h3>
                    <p className="mt-2 text-3xl font-bold text-gray-900">{stats.draft}</p>
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="text-sm font-medium text-gray-500">Calculated</h3>
                    <p className="mt-2 text-3xl font-bold text-green-600">{stats.calculated}</p>
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="text-sm font-medium text-gray-500">Finalized</h3>
                    <p className="mt-2 text-3xl font-bold text-primary">{stats.finalized}</p>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="text-sm font-medium text-gray-500">Your Email</h3>
                    <p className="mt-2 text-lg font-semibold text-gray-900">{auth.user?.email}</p>
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="text-sm font-medium text-gray-500">Quick Actions</h3>
                    <div className="mt-3 flex flex-col gap-2">
                        <Link href="/v2/estimations" className="text-sm text-primary hover:text-primary/80 font-medium transition">
                            View Estimations &rarr;
                        </Link>
                        <Link href="/v2/projects" className="text-sm text-primary hover:text-primary/80 font-medium transition">
                            View Projects &rarr;
                        </Link>
                        <Link href="/v2/reports" className="text-sm text-primary hover:text-primary/80 font-medium transition">
                            View Reports &rarr;
                        </Link>
                    </div>
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 className="text-sm font-medium text-gray-500">Role</h3>
                    <p className="mt-2 text-lg font-semibold text-gray-900 capitalize">{auth.user?.role}</p>
                    {auth.user?.company_name && (
                        <p className="text-sm text-gray-500 mt-1">{auth.user.company_name}</p>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
