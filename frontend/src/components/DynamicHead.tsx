"use client";

import { useEffect } from "react";
import { useBranding } from "@/contexts/BrandingContext";

/**
 * Client-side component that dynamically updates the document title
 * and favicon based on admin-configured branding settings.
 */
export default function DynamicHead() {
  const { branding } = useBranding();

  useEffect(() => {
    document.title = branding.app_name;
  }, [branding.app_name]);

  useEffect(() => {
    if (!branding.favicon_url) {
      return;
    }

    let link = document.querySelector(
      "link[rel='icon']"
    ) as HTMLLinkElement | null;

    if (!link) {
      link = document.createElement("link");
      link.rel = "icon";
      document.head.appendChild(link);
    }

    link.href = branding.favicon_url;
  }, [branding.favicon_url]);

  useEffect(() => {
    document.documentElement.style.setProperty(
      "--color-primary-runtime",
      branding.primary_color
    );
  }, [branding.primary_color]);

  return null;
}
