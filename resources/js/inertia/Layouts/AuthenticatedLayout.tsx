import { Head, Link, usePage } from '@inertiajs/react';
import { type PageProps } from '../types';
import { type PropsWithChildren, useState, useRef, useEffect } from 'react';
import { useDynamicBranding } from '../hooks/useDynamicBranding';

const NAV_LINKS = [
    { href: '/v2/dashboard', label: 'Dashboard' },
    { href: '/v2/estimations', label: 'Estimations' },
    { href: '/v2/projects', label: 'Projects' },
    { href: '/v2/reports', label: 'Reports' },
];

export default function AuthenticatedLayout({
    children,
    title,
    fullWidth = false,
    hideNav = false,
}: PropsWithChildren<{
    title?: string;
    fullWidth?: boolean;
    hideNav?: boolean;
}>) {
    useDynamicBranding();
    const { auth, flash, appSettings } = usePage<PageProps>().props;
    const currentUrl = usePage().url;
    const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);
    const userMenuRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (
                userMenuRef.current &&
                !userMenuRef.current.contains(event.target as Node)
            ) {
                setIsUserMenuOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () =>
            document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const isActive = (href: string): boolean => {
        if (href === '/v2/dashboard') {
            return currentUrl === '/v2/dashboard';
        }
        return currentUrl.startsWith(href);
    };

    return (
        <>
            <Head title={title} />
            <div className="min-h-screen bg-gray-100">
                {!hideNav && (
                <nav className="bg-white border-b border-gray-200">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center h-16">
                            <div className="flex items-center gap-6">
                                <Link
                                    href="/v2/dashboard"
                                    className="flex items-center gap-2"
                                >
                                    {appSettings?.logo_url ? (
                                        <img
                                            src={appSettings.logo_url}
                                            alt={
                                                appSettings?.company_name ??
                                                'Maimaar'
                                            }
                                            className="h-8 w-auto object-contain"
                                        />
                                    ) : (
                                        <span className="text-xl font-bold text-gray-900">
                                            {appSettings?.company_name ??
                                                'Maimaar'}
                                        </span>
                                    )}
                                </Link>
                                <div className="hidden sm:flex items-center gap-4">
                                    {NAV_LINKS.map((link) => (
                                        <Link
                                            key={link.href}
                                            href={link.href}
                                            className={`text-sm transition ${
                                                isActive(link.href)
                                                    ? 'text-gray-900 font-medium'
                                                    : 'text-gray-600 hover:text-gray-900'
                                            }`}
                                        >
                                            {link.label}
                                        </Link>
                                    ))}
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                {/* User dropdown */}
                                <div className="relative" ref={userMenuRef}>
                                    <button
                                        onClick={() =>
                                            setIsUserMenuOpen(!isUserMenuOpen)
                                        }
                                        className="flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900 transition rounded-lg px-2 py-1.5 hover:bg-gray-50"
                                    >
                                        <div className="w-7 h-7 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-semibold">
                                            {auth.user?.name
                                                ?.charAt(0)
                                                ?.toUpperCase() ?? 'U'}
                                        </div>
                                        <span className="hidden sm:inline">
                                            {auth.user?.name}
                                        </span>
                                        <svg
                                            className={`w-4 h-4 text-gray-400 transition-transform ${isUserMenuOpen ? 'rotate-180' : ''}`}
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={2}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M19 9l-7 7-7-7"
                                            />
                                        </svg>
                                    </button>

                                    {isUserMenuOpen && (
                                        <div className="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                            <div className="px-3 py-2 border-b border-gray-100">
                                                <p className="text-sm font-medium text-gray-900 truncate">
                                                    {auth.user?.name}
                                                </p>
                                                <p className="text-xs text-gray-500 truncate">
                                                    {auth.user?.email}
                                                </p>
                                            </div>
                                            <Link
                                                href="/v2/profile"
                                                onClick={() =>
                                                    setIsUserMenuOpen(false)
                                                }
                                                className="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                                            >
                                                Profile Settings
                                            </Link>
                                            <Link
                                                href="/v2/logout"
                                                method="post"
                                                as="button"
                                                onClick={() =>
                                                    setIsUserMenuOpen(false)
                                                }
                                                className="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition"
                                            >
                                                Logout
                                            </Link>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>
                )}

                {flash?.success && (
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-4">
                        <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                            {flash.success}
                        </div>
                    </div>
                )}
                {flash?.error && (
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-4">
                        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                            {flash.error}
                        </div>
                    </div>
                )}

                <main className={fullWidth ? '' : 'py-6'}>
                    <div
                        className={
                            fullWidth
                                ? ''
                                : 'max-w-7xl mx-auto sm:px-6 lg:px-8'
                        }
                    >
                        {children}
                    </div>
                </main>
            </div>
        </>
    );
}
