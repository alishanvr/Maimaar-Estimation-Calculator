"use client";

import { useState, useEffect, useCallback } from "react";
import type { Estimation, PaginatedResponse } from "@/types";
import { listEstimations } from "@/lib/estimations";

export function useEstimations(statusFilter?: string) {
  const [data, setData] = useState<PaginatedResponse<Estimation> | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);

  const fetch = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const result = await listEstimations({
        page,
        status: statusFilter || undefined,
      });
      setData(result);
    } catch {
      setError("Failed to load estimations.");
    } finally {
      setIsLoading(false);
    }
  }, [page, statusFilter]);

  useEffect(() => {
    fetch();
  }, [fetch]);

  return {
    estimations: data?.data ?? [],
    meta: data?.meta ?? null,
    isLoading,
    error,
    page,
    setPage,
    refetch: fetch,
  };
}
