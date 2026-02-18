"use client";

import { useState, useCallback } from "react";
import { useSheetData } from "@/hooks/useSheetData";
import { useCurrency } from "@/hooks/useCurrency";
import { formatNumber } from "@/lib/formatters";
import { exportRecapPdf } from "@/lib/estimations";
import { downloadBlob } from "@/lib/download";
import type { RecapData } from "@/types";
import ReadOnlySheet from "./ReadOnlySheet";
import ExportButtons from "./ExportButtons";

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
  const { symbol, format, formatPerMT } = useCurrency();
  const { data, isLoading, error } = useSheetData<RecapData>(
    estimationId,
    "recap",
    version
  );

  const [downloading, setDownloading] = useState(false);

  const handleDownloadPdf = useCallback(async () => {
    setDownloading(true);
    try {
      const blob = await exportRecapPdf(estimationId);
      downloadBlob(blob, `Recap-${estimationId}.pdf`);
    } finally {
      setDownloading(false);
    }
  }, [estimationId]);

  return (
    <ReadOnlySheet isLoading={isLoading} error={error} sheetLabel="Recap">
      <div className="p-6">
        {/* Download button bar */}
        <div className="flex items-center gap-2 mb-6 no-print">
          <ExportButtons
            estimationId={estimationId}
            sheetType="recap"
            onDownloadPdf={handleDownloadPdf}
            downloadingPdf={downloading}
          />
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
                value={format(data.total_price_aed, 2)}
              />
              <StatCard
                label="FOB Price"
                value={format(data.fob_price_aed, 2)}
              />
              <StatCard
                label="Price per MT"
                value={formatPerMT(data.price_per_mt, 2)}
              />
            </div>
          </div>
        )}
      </div>
    </ReadOnlySheet>
  );
}
