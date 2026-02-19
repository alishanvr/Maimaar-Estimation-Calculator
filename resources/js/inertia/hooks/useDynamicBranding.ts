import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import type { PageProps } from '../types';

/**
 * Applies admin-configured branding to the document:
 *  - Sets --color-primary CSS variable for dynamic theming
 *  - Updates the favicon
 */
export function useDynamicBranding(): void {
    const { appSettings } = usePage<PageProps>().props;

    useEffect(() => {
        if (appSettings?.primary_color) {
            document.documentElement.style.setProperty(
                '--color-primary',
                appSettings.primary_color,
            );
        }
    }, [appSettings?.primary_color]);

    useEffect(() => {
        if (!appSettings?.favicon_url) {
            return;
        }

        let link = document.querySelector(
            "link[rel='icon']",
        ) as HTMLLinkElement | null;

        if (!link) {
            link = document.createElement('link');
            link.rel = 'icon';
            document.head.appendChild(link);
        }

        link.href = appSettings.favicon_url;
    }, [appSettings?.favicon_url]);
}
