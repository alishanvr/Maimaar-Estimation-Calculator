"use client";

import { useState, useEffect, useCallback, useRef } from "react";
import type { Estimation, InputData, Markups } from "@/types";
import {
  getEstimation,
  updateEstimation,
  calculateEstimation,
} from "@/lib/estimations";

export function useEstimation(id: number) {
  const [estimation, setEstimation] = useState<Estimation | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [isCalculating, setIsCalculating] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const saveTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const fetch = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await getEstimation(id);
      setEstimation(data);
    } catch {
      setError("Failed to load estimation.");
    } finally {
      setIsLoading(false);
    }
  }, [id]);

  useEffect(() => {
    fetch();
  }, [fetch]);

  /** Immediately save the estimation to the server. */
  const save = useCallback(
    async (updates: Partial<Estimation>) => {
      if (!estimation) return;
      setIsSaving(true);
      setError(null);
      try {
        const data = await updateEstimation(estimation.id, updates);
        setEstimation(data);
      } catch {
        setError("Failed to save estimation.");
      } finally {
        setIsSaving(false);
      }
    },
    [estimation]
  );

  /** Debounced save — waits 800ms after last call before saving. */
  const debouncedSave = useCallback(
    (updates: Partial<Estimation>) => {
      if (saveTimerRef.current) clearTimeout(saveTimerRef.current);
      saveTimerRef.current = setTimeout(() => save(updates), 800);
    },
    [save]
  );

  /** Update input_data and trigger debounced save. */
  const updateInputData = useCallback(
    (inputData: InputData) => {
      if (!estimation) return;
      setEstimation((prev) =>
        prev ? { ...prev, input_data: inputData, status: "draft" } : prev
      );
      debouncedSave({ input_data: inputData });
    },
    [estimation, debouncedSave]
  );

  /** Trigger server-side calculation. */
  const calculate = useCallback(
    async (markups?: Markups) => {
      if (!estimation) return;
      setIsCalculating(true);
      setError(null);
      try {
        const data = await calculateEstimation(estimation.id, markups);
        setEstimation(data);
      } catch (err: unknown) {
        const message =
          err instanceof Error ? err.message : "Calculation failed.";
        setError(message);
      } finally {
        setIsCalculating(false);
      }
    },
    [estimation]
  );

  /** Update top-level estimation fields (quote_number, building_name, etc.) and save. */
  const updateFields = useCallback(
    (fields: Partial<Estimation>) => {
      if (!estimation) return;
      setEstimation((prev) => (prev ? { ...prev, ...fields } : prev));
      debouncedSave(fields);
    },
    [estimation, debouncedSave]
  );

  // Cleanup timer on unmount
  useEffect(() => {
    return () => {
      if (saveTimerRef.current) clearTimeout(saveTimerRef.current);
    };
  }, []);

  return {
    estimation,
    isLoading,
    isSaving,
    isCalculating,
    error,
    save,
    updateInputData,
    updateFields,
    calculate,
    refetch: fetch,
  };
}
