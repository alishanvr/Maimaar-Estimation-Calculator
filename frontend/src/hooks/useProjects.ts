"use client";

import { useState, useEffect, useCallback } from "react";
import type { Project, PaginatedResponse } from "@/types";
import { listProjects } from "@/lib/projects";

export function useProjects(statusFilter?: string, search?: string) {
  const [data, setData] = useState<PaginatedResponse<Project> | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);

  const fetch = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const result = await listProjects({
        page,
        status: statusFilter || undefined,
        search: search || undefined,
      });
      setData(result);
    } catch {
      setError("Failed to load projects.");
    } finally {
      setIsLoading(false);
    }
  }, [page, statusFilter, search]);

  useEffect(() => {
    fetch();
  }, [fetch]);

  return {
    projects: data?.data ?? [],
    meta: data?.meta ?? null,
    isLoading,
    error,
    page,
    setPage,
    refetch: fetch,
  };
}
