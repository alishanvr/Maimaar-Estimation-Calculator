"use client";

import { useParams, usePathname } from "next/navigation";

/**
 * Returns the real dynamic route parameter from the URL pathname.
 *
 * With Next.js static export, `useParams()` may return the pre-rendered
 * placeholder value (e.g. "0") during initial hydration instead of the
 * actual URL segment. This hook cross-checks against `usePathname()` and
 * falls back to extracting the segment directly from the pathname when
 * the values disagree.
 */
export function useRealParam(name: string, segmentIndex: number): string {
  const params = useParams();
  const pathname = usePathname();

  const paramValue = params[name] as string | undefined;
  const segments = pathname.replace(/\/$/, "").split("/").filter(Boolean);
  const pathValue = segments[segmentIndex];

  // Prefer the pathname value when it differs from the pre-rendered param
  if (pathValue && paramValue !== pathValue) {
    return pathValue;
  }

  return paramValue ?? pathValue ?? "";
}
