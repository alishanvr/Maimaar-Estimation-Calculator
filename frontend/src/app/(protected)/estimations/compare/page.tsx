"use client";

import { useState, useEffect } from "react";
import { useSearchParams } from "next/navigation";
import Link from "next/link";
import { compareEstimations } from "@/lib/estimations";
import type { ComparisonEstimation, EstimationStatus } from "@/types";

const STATUS_BADGE: Record<EstimationStatus, string> = {
  draft: "bg-gray-100 text-gray-700",
  calculated: "bg-green-100 text-green-700",
  finalized: "bg-primary/15 text-primary",
};

interface MetricRow {
  label: string;
  key: string;
  decimals: number;
  unit: string;
  getValue: (est: ComparisonEstimation) => number | null;
}

const METRICS: MetricRow[] = [
  {
    label: "Total Weight",
    key: "total_weight_mt",
    decimals: 2,
    unit: "MT",
    getValue: (e) => e.total_weight_mt,
  },
  {
    label: "Total Price",
    key: "total_price_aed",
    decimals: 0,
    unit: "AED",
    getValue: (e) => e.total_price_aed,
  },
  {
    label: "Price / MT",
    key: "price_per_mt",
    decimals: 2,
    unit: "AED",
    getValue: (e) => e.summary?.price_per_mt ?? null,
  },
  {
    label: "FOB Price",
    key: "fob_price",
    decimals: 0,
    unit: "AED",
    getValue: (e) => e.summary?.fob_price_aed ?? null,
  },
  {
    label: "Steel Weight",
    key: "steel_weight_kg",
    decimals: 0,
    unit: "kg",
    getValue: (e) => e.summary?.steel_weight_kg ?? null,
  },
  {
    label: "Panels Weight",
    key: "panels_weight_kg",
    decimals: 0,
    unit: "kg",
    getValue: (e) => e.summary?.panels_weight_kg ?? null,
  },
];

export default function ComparePage() {
  const searchParams = useSearchParams();
  const idsParam = searchParams.get("ids");
  const ids = idsParam ? idsParam.split(",").map(Number) : [];

  const [data, setData] = useState<ComparisonEstimation[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (ids.length !== 2) {
      setError("Please select exactly 2 estimations to compare.");
      setIsLoading(false);
      return;
    }

    compareEstimations(ids)
      .then(setData)
      .catch((err) => {
        setError(
          err?.response?.data?.message || "Failed to load comparison data."
        );
      })
      .finally(() => setIsLoading(false));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [idsParam]);

  const formatNumber = (value: number | null, decimals = 2): string => {
    if (value === null || value === undefined) return "\u2014";
    return value.toLocaleString("en-US", {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    });
  };

  const getDelta = (
    a: number | null,
    b: number | null
  ): { value: number | null; pct: number | null } => {
    if (a === null || b === null) return { value: null, pct: null };
    const diff = b - a;
    const pct = a !== 0 ? (diff / a) * 100 : null;
    return { value: diff, pct };
  };

  // Gather input_data keys that differ
  const getInputDifferences = (): string[] => {
    if (data.length < 2) return [];
    const a = data[0].input_data || {};
    const b = data[1].input_data || {};
    const allKeys = new Set([...Object.keys(a), ...Object.keys(b)]);
    const diffs: string[] = [];
    for (const key of allKeys) {
      if (JSON.stringify(a[key]) !== JSON.stringify(b[key])) {
        diffs.push(key);
      }
    }
    return diffs.sort();
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <p className="text-gray-400">Loading comparison...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-xl mx-auto mt-12 text-center">
        <div className="bg-red-50 text-red-700 rounded-lg p-4 border border-red-200 mb-4">
          {error}
        </div>
        <Link
          href="/estimations"
          className="text-primary hover:underline text-sm"
        >
          &larr; Back to Estimations
        </Link>
      </div>
    );
  }

  const [estA, estB] = data;
  const inputDiffs = getInputDifferences();

  return (
    <div>
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <Link
            href="/estimations"
            className="text-gray-400 hover:text-gray-600 transition text-sm"
          >
            &larr; Back to Estimations
          </Link>
          <h2 className="text-2xl font-bold text-gray-900 mt-1">
            Estimation Comparison
          </h2>
        </div>
      </div>

      {/* Estimation Headers */}
      <div className="grid grid-cols-3 gap-4 mb-6">
        <div className="col-span-1" />
        {[estA, estB].map((est, i) => (
          <div
            key={est.id}
            className="bg-white rounded-lg border border-gray-200 p-4"
          >
            <div className="flex items-center gap-2 mb-1">
              <span className="text-xs text-gray-400">
                Estimation {i + 1}
              </span>
              <span
                className={`inline-block px-2 py-0.5 rounded-full text-[10px] font-medium ${STATUS_BADGE[est.status]}`}
              >
                {est.status}
              </span>
            </div>
            <p className="font-mono text-sm font-medium text-gray-900">
              {est.quote_number || "\u2014"}
            </p>
            <p className="text-sm text-gray-600">
              {est.building_name || "Untitled"}
            </p>
            {est.revision_no && (
              <p className="text-xs text-gray-400 mt-1">
                Rev: {est.revision_no}
              </p>
            )}
          </div>
        ))}
      </div>

      {/* Key Metrics Table */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div className="px-4 py-3 bg-gray-50 border-b border-gray-200">
          <h3 className="font-medium text-gray-900">Key Metrics</h3>
        </div>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-gray-200">
              <th className="text-left px-4 py-2 font-medium text-gray-500">
                Metric
              </th>
              <th className="text-right px-4 py-2 font-medium text-gray-500">
                {estA.quote_number || `#${estA.id}`}
              </th>
              <th className="text-right px-4 py-2 font-medium text-gray-500">
                {estB.quote_number || `#${estB.id}`}
              </th>
              <th className="text-right px-4 py-2 font-medium text-gray-500">
                Delta
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {METRICS.map((m) => {
              const valA = m.getValue(estA);
              const valB = m.getValue(estB);
              const delta = getDelta(valA, valB);
              return (
                <tr key={m.key}>
                  <td className="px-4 py-2 font-medium text-gray-700">
                    {m.label}
                  </td>
                  <td className="px-4 py-2 text-right font-mono text-xs">
                    {formatNumber(valA, m.decimals)} {m.unit}
                  </td>
                  <td className="px-4 py-2 text-right font-mono text-xs">
                    {formatNumber(valB, m.decimals)} {m.unit}
                  </td>
                  <td className="px-4 py-2 text-right font-mono text-xs">
                    {delta.value !== null ? (
                      <span
                        className={
                          delta.value > 0
                            ? "text-red-600"
                            : delta.value < 0
                              ? "text-green-600"
                              : "text-gray-400"
                        }
                      >
                        {delta.value > 0 ? "+" : ""}
                        {formatNumber(delta.value, m.decimals)}
                        {delta.pct !== null && (
                          <span className="ml-1 text-[10px]">
                            ({delta.pct > 0 ? "+" : ""}
                            {delta.pct.toFixed(1)}%)
                          </span>
                        )}
                      </span>
                    ) : (
                      "\u2014"
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      {/* Input Differences */}
      {inputDiffs.length > 0 && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div className="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <h3 className="font-medium text-gray-900">
              Input Differences ({inputDiffs.length})
            </h3>
          </div>
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="text-left px-4 py-2 font-medium text-gray-500">
                  Field
                </th>
                <th className="text-right px-4 py-2 font-medium text-gray-500">
                  {estA.quote_number || `#${estA.id}`}
                </th>
                <th className="text-right px-4 py-2 font-medium text-gray-500">
                  {estB.quote_number || `#${estB.id}`}
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {inputDiffs.map((key) => {
                const valA = estA.input_data?.[key];
                const valB = estB.input_data?.[key];
                const display = (v: unknown): string => {
                  if (v === null || v === undefined) return "\u2014";
                  if (typeof v === "object") return JSON.stringify(v);
                  return String(v);
                };
                return (
                  <tr key={key}>
                    <td className="px-4 py-2 font-mono text-xs text-gray-700">
                      {key}
                    </td>
                    <td className="px-4 py-2 text-right text-xs text-gray-600">
                      {display(valA)}
                    </td>
                    <td className="px-4 py-2 text-right text-xs text-gray-600">
                      {display(valB)}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}

      {inputDiffs.length === 0 && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center text-gray-400">
          No input differences found between these estimations.
        </div>
      )}
    </div>
  );
}
