"use client";

import { useState, useCallback } from "react";
import { useSheetData } from "@/hooks/useSheetData";
import { formatNumber } from "@/lib/formatters";
import { exportRecapPdf } from "@/lib/estimations";
import { downloadBlob } from "@/lib/download";
import type { RecapData } from "@/types";
import ReadOnlySheet from "./ReadOnlySheet";

interface RecapSheetProps {
  estimationId: number;
  version?: string;
}

function StatCard({
  label,
  value,
  unit,
}: {
  label: string;
  value: string;
  unit?: string;
}) {
  return (
    <div className="flex justify-between items-baseline py-3 border-b border-gray-100 last:border-0">
      <span className="text-sm text-gray-500">{label}</span>
      <span className="text-sm font-mono font-medium text-gray-900">
        {value}
        {unit && <span className="text-gray-400 ml-1">{unit}</span>}
      </span>
    </div>
  );
}

export default function RecapSheet({ estimationId, version }: RecapSheetProps) {
  const { data, isLoading, error } = useSheetData<RecapData>(
    estimationId,
    "recap",
    version
  );

  const [downloading, setDownloading] = useState(false);
  const [downloadError, setDownloadError] = useState<string | null>(null);

  const handleDownloadPdf = useCallback(async () => {
    setDownloading(true);
    setDownloadError(null);
    try {
      const blob = await exportRecapPdf(estimationId);
      downloadBlob(blob, `Recap-${estimationId}.pdf`);
    } catch (err: unknown) {
      const message =
        err instanceof Error ? err.message : "Failed to download PDF";
      setDownloadError(message);
    } finally {
      setDownloading(false);
    }
  }, [estimationId]);

  return (
    <ReadOnlySheet isLoading={isLoading} error={error} sheetLabel="Recap">
      <div className="p-6">
        {/* Download button bar */}
        <div className="flex items-center gap-2 mb-6 no-print">
          <button
            onClick={handleDownloadPdf}
            disabled={downloading}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg
              className="w-3.5 h-3.5"
              fill="none"
              viewBox="0 0 24 24"
              strokeWidth={2}
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"
              />
            </svg>
            {downloading ? "Downloading..." : "Download PDF"}
          </button>
          {downloadError && (
            <span className="text-xs text-red-500">{downloadError}</span>
          )}
        </div>

        <h3 className="text-lg font-bold text-gray-900 mb-6">
          Estimation Summary
        </h3>

        {data && (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl">
            {/* Weight Breakdown */}
            <div className="bg-white rounded-xl border border-gray-200 p-5">
              <h4 className="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
                Weight Breakdown
              </h4>
              <StatCard
                label="Total Weight"
                value={formatNumber(data.total_weight_kg, 1)}
                unit="kg"
              />
              <StatCard
                label="Total Weight"
                value={formatNumber(data.total_weight_mt, 4)}
                unit="MT"
              />
              <StatCard
                label="Steel Weight"
                value={formatNumber(data.steel_weight_kg, 1)}
                unit="kg"
              />
              <StatCard
                label="Panels Weight"
                value={formatNumber(data.panels_weight_kg, 1)}
                unit="kg"
              />
            </div>

            {/* Price Breakdown */}
            <div className="bg-white rounded-xl border border-gray-200 p-5">
              <h4 className="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
                Price Breakdown
              </h4>
              <StatCard
                label="Total Price"
                value={formatNumber(data.total_price_aed, 2)}
                unit="AED"
              />
              <StatCard
                label="FOB Price"
                value={formatNumber(data.fob_price_aed, 2)}
                unit="AED"
              />
              <StatCard
                label="Price per MT"
                value={formatNumber(data.price_per_mt, 2)}
                unit="AED/MT"
              />
            </div>
          </div>
        )}
      </div>
    </ReadOnlySheet>
  );
}
