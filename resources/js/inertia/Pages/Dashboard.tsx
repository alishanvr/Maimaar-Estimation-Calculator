import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';
import { usePage } from '@inertiajs/react';
import { type PageProps } from '../types';

export default function Dashboard() {
    const { auth } = usePage<PageProps>().props;

    return (
        <AuthenticatedLayout title="Dashboard">
            <div className="bg-white overflow-hidden shadow-sm rounded-lg">
                <div className="p-6 text-gray-900">
                    <h2 className="text-lg font-semibold mb-4">Dashboard</h2>
                    <p>
                        Welcome back, {auth.user?.name}. This is the Inertia.js
                        + React frontend (v2).
                    </p>
                    <p className="mt-2 text-sm text-gray-500">
                        Role: {auth.user?.role} | Company:{' '}
                        {auth.user?.company_name ?? 'N/A'}
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
