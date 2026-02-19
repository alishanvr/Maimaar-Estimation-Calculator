import { Head, Link, usePage } from '@inertiajs/react';
import { type PageProps } from '../types';
import { type PropsWithChildren } from 'react';

export default function AuthenticatedLayout({
    children,
    title,
}: PropsWithChildren<{ title?: string }>) {
    const { auth, flash } = usePage<PageProps>().props;

    return (
        <>
            <Head title={title} />
            <div className="min-h-screen bg-gray-100">
                <nav className="bg-white border-b border-gray-200">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex items-center">
                                <span className="text-xl font-semibold">
                                    Maimaar v2
                                </span>
                                <Link
                                    href="/v2/dashboard"
                                    className="ml-8 text-gray-700 hover:text-gray-900"
                                >
                                    Dashboard
                                </Link>
                            </div>
                            <div className="flex items-center gap-4">
                                {auth.user && (
                                    <span className="text-sm text-gray-600">
                                        {auth.user.name}
                                    </span>
                                )}
                                <Link
                                    href="/v2/logout"
                                    method="post"
                                    as="button"
                                    className="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    Logout
                                </Link>
                            </div>
                        </div>
                    </div>
                </nav>

                {flash.success && (
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-4">
                        <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                            {flash.success}
                        </div>
                    </div>
                )}
                {flash.error && (
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-4">
                        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                            {flash.error}
                        </div>
                    </div>
                )}

                <main className="py-6">
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        {children}
                    </div>
                </main>
            </div>
        </>
    );
}
