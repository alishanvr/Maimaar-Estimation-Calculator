import { Head } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';
import { useDynamicBranding } from '../hooks/useDynamicBranding';

export default function GuestLayout({
    children,
    title,
}: PropsWithChildren<{ title?: string }>) {
    useDynamicBranding();

    return (
        <>
            <Head title={title} />
            <div className="min-h-screen flex items-center justify-center bg-gray-100">
                <div className="w-full max-w-md">{children}</div>
            </div>
        </>
    );
}
