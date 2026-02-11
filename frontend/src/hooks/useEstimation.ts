"use client";

import { useState, useEffect, useCallback, useRef } from "react";
import type { Estimation, InputData, Markups } from "@/types";
import {
  getEstimation,
  updateEstimation,
  calculateEstimation,
  finalizeEstimation,
  unlockEstimation,
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
      } catch (err: unknown) {
        let message = "Failed to save estimation.";
        if (err && typeof err === "object" && "response" in err) {
          const axiosErr = err as { response?: { data?: { message?: string } } };
          message = axiosErr.response?.data?.message || message;
        }
        setError(message);
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
        let message = "Calculation failed.";
        if (err && typeof err === "object" && "response" in err) {
          const axiosErr = err as { response?: { data?: { message?: string } } };
          message = axiosErr.response?.data?.message || message;
        } else if (err instanceof Error) {
          message = err.message;
        }
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

  /**
   * Save all fields immediately (no debounce) and then calculate.
   * Used by "Fill Test Data" to avoid race conditions between debounced saves and calculate.
   */
  const saveAndCalculate = useCallback(
    async (
      fields: Partial<Estimation>,
      inputData: InputData,
      markups?: Markups
    ) => {
      if (!estimation) return;

      // Cancel any pending debounced saves
      if (saveTimerRef.current) clearTimeout(saveTimerRef.current);

      // Update local state immediately
      setEstimation((prev) =>
        prev
          ? { ...prev, ...fields, input_data: inputData, status: "draft" }
          : prev
      );

      // Single immediate save with ALL data
      setIsSaving(true);
      setError(null);
      try {
        await updateEstimation(estimation.id, {
          ...fields,
          input_data: inputData,
        });
      } catch (err: unknown) {
        let message = "Failed to save estimation.";
        if (err && typeof err === "object" && "response" in err) {
          const axiosErr = err as { response?: { data?: { message?: string } } };
          message = axiosErr.response?.data?.message || message;
        }
        setError(message);
        setIsSaving(false);
        return;
      }
      setIsSaving(false);

      // Now calculate — data is persisted in DB
      setIsCalculating(true);
      try {
        const data = await calculateEstimation(estimation.id, markups);
        setEstimation(data);
      } catch (err: unknown) {
        let message = "Calculation failed.";
        if (err && typeof err === "object" && "response" in err) {
          const axiosErr = err as { response?: { data?: { message?: string } } };
          message = axiosErr.response?.data?.message || message;
        } else if (err instanceof Error) {
          message = err.message;
        }
        setError(message);
      } finally {
        setIsCalculating(false);
      }
    },
    [estimation]
  );

  /** Mark estimation as finalized (read-only). */
  const finalize = useCallback(async () => {
    if (!estimation) return;
    setIsSaving(true);
    setError(null);
    try {
      const data = await finalizeEstimation(estimation.id);
      setEstimation(data);
    } catch (err: unknown) {
      let message = "Failed to finalize estimation.";
      if (err && typeof err === "object" && "response" in err) {
        const axiosErr = err as { response?: { data?: { message?: string } } };
        message = axiosErr.response?.data?.message || message;
      }
      setError(message);
    } finally {
      setIsSaving(false);
    }
  }, [estimation]);

  /** Unlock a finalized estimation back to draft. */
  const unlock = useCallback(async () => {
    if (!estimation) return;
    setIsSaving(true);
    setError(null);
    try {
      const data = await unlockEstimation(estimation.id);
      setEstimation(data);
    } catch (err: unknown) {
      let message = "Failed to unlock estimation.";
      if (err && typeof err === "object" && "response" in err) {
        const axiosErr = err as { response?: { data?: { message?: string } } };
        message = axiosErr.response?.data?.message || message;
      }
      setError(message);
    } finally {
      setIsSaving(false);
    }
  }, [estimation]);

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
    saveAndCalculate,
    finalize,
    unlock,
    refetch: fetch,
  };
}
