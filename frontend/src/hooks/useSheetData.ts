"use client";

import { useState, useEffect, useRef } from "react";
import { getSheetData } from "@/lib/estimations";
import type { SheetTab } from "@/types";

interface UseSheetDataResult<T> {
  data: T | null;
  isLoading: boolean;
  error: string | null;
}

/**
 * Hook to fetch sheet data for a calculated estimation.
 * Fetches once on mount and caches the result.
 * Re-fetches when estimationId or version changes (e.g. after recalculation).
 */
export function useSheetData<T>(
  estimationId: number,
  sheet: Exclude<SheetTab, "input">,
  version?: string
): UseSheetDataResult<T> {
  const [data, setData] = useState<T | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const cacheRef = useRef<{
    id: number;
    sheet: string;
    version?: string;
    data: T;
  } | null>(null);

  useEffect(() => {
    // Return cached data if same estimation + sheet + version
    if (
      cacheRef.current &&
      cacheRef.current.id === estimationId &&
      cacheRef.current.sheet === sheet &&
      cacheRef.current.version === version
    ) {
      setData(cacheRef.current.data);
      setIsLoading(false);
      return;
    }

    let cancelled = false;
    setIsLoading(true);
    setError(null);

    getSheetData(estimationId, sheet)
      .then((result) => {
        if (!cancelled) {
          const typed = result as T;
          cacheRef.current = { id: estimationId, sheet, version, data: typed };
          setData(typed);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setError(`Failed to load ${sheet} data.`);
        }
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [estimationId, sheet, version]);

  return { data, isLoading, error };
}
