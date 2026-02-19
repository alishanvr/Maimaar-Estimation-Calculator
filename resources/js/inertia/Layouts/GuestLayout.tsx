import { Head } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

export default function GuestLayout({
    children,
    title,
}: PropsWithChildren<{ title?: string }>) {
    return (
        <>
            <Head title={title} />
            <div className="min-h-screen flex items-center justify-center bg-gray-100">
                <div className="w-full max-w-md">{children}</div>
            </div>
        </>
    );
}
