"use client";

import { useState, useEffect } from "react";
import type { DesignConfiguration } from "@/types";
import {
  getDesignConfigurations,
  getPaintSystems,
  getFreightCodes,
} from "@/lib/estimations";

/** In-memory cache so repeated calls for the same category don't re-fetch. */
const cache: Record<string, DesignConfiguration[]> = {};

export function useDesignConfigurations(category: string) {
  const [options, setOptions] = useState<DesignConfiguration[]>(
    cache[category] ?? []
  );
  const [isLoading, setIsLoading] = useState(!cache[category]);

  useEffect(() => {
    if (cache[category]) {
      setOptions(cache[category]);
      setIsLoading(false);
      return;
    }

    let cancelled = false;
    setIsLoading(true);

    const fetchFn =
      category === "paint_system"
        ? getPaintSystems
        : category === "freight_code"
          ? getFreightCodes
          : () => getDesignConfigurations(category);

    fetchFn().then((data) => {
      if (!cancelled) {
        cache[category] = data;
        setOptions(data);
        setIsLoading(false);
      }
    });

    return () => {
      cancelled = true;
    };
  }, [category]);

  /** Get labels suitable for a Handsontable dropdown source. */
  const dropdownLabels = options.map((opt) => opt.label || opt.value);

  /** Find the value for a given label. */
  const valueForLabel = (label: string): string => {
    const match = options.find(
      (opt) => opt.label === label || opt.value === label
    );
    return match?.value ?? label;
  };

  /** Find the label for a given value. */
  const labelForValue = (value: string): string => {
    const match = options.find(
      (opt) => opt.value === value || opt.key === value
    );
    return match?.label ?? value;
  };

  return { options, dropdownLabels, isLoading, valueForLabel, labelForValue };
}
