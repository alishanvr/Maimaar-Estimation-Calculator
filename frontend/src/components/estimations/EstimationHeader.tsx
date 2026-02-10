"use client";

import Link from "next/link";
import type { Estimation, EstimationStatus } from "@/types";

const STATUS_BADGE: Record<EstimationStatus, string> = {
  draft: "bg-gray-100 text-gray-700",
  calculated: "bg-green-100 text-green-700",
  finalized: "bg-blue-100 text-blue-700",
};

interface EstimationHeaderProps {
  estimation: Estimation;
  isSaving: boolean;
  isCalculating: boolean;
  onSave: () => void;
  onCalculate: () => void;
}

export default function EstimationHeader({
  estimation,
  isSaving,
  isCalculating,
  onSave,
  onCalculate,
}: EstimationHeaderProps) {
  const formatNumber = (value: number | null, decimals = 2): string => {
    if (value === null || value === undefined) return "\u2014";
    return value.toLocaleString("en-US", {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    });
  };

  return (
    <div className="bg-white border-b border-gray-200 px-4 py-3">
      <div className="flex items-center justify-between">
        {/* Left: Back + Project Info */}
        <div className="flex items-center gap-4">
          <Link
            href="/estimations"
            className="text-gray-400 hover:text-gray-600 transition text-sm"
          >
            &larr; Back
          </Link>
          <div className="h-6 w-px bg-gray-200" />
          <div className="flex items-center gap-3">
            <div>
              <span className="text-xs text-gray-400">Quote</span>
              <p className="text-sm font-mono font-medium text-gray-900">
                {estimation.quote_number || "—"}
              </p>
            </div>
            <div className="h-8 w-px bg-gray-100" />
            <div>
              <span className="text-xs text-gray-400">Building</span>
              <p className="text-sm font-medium text-gray-900">
                {estimation.building_name || "Untitled"}
              </p>
            </div>
            <div className="h-8 w-px bg-gray-100" />
            <span
              className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${STATUS_BADGE[estimation.status]}`}
            >
              {estimation.status}
            </span>
          </div>
        </div>

        {/* Center: Summary Stats */}
        <div className="hidden md:flex items-center gap-6">
          <div className="text-center">
            <span className="text-xs text-gray-400">Total Weight</span>
            <p className="text-sm font-mono font-semibold text-gray-900">
              {formatNumber(estimation.total_weight_mt, 2)} MT
            </p>
          </div>
          <div className="text-center">
            <span className="text-xs text-gray-400">Total Price</span>
            <p className="text-sm font-mono font-semibold text-gray-900">
              {formatNumber(estimation.total_price_aed, 0)} AED
            </p>
          </div>
        </div>

        {/* Right: Actions */}
        <div className="flex items-center gap-2">
          {isSaving && (
            <span className="text-xs text-gray-400 mr-2">Saving...</span>
          )}
          <button
            onClick={onSave}
            disabled={isSaving}
            className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition disabled:opacity-50"
          >
            Save
          </button>
          <button
            onClick={onCalculate}
            disabled={isCalculating}
            className="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-50"
          >
            {isCalculating ? "Calculating..." : "Calculate"}
          </button>
        </div>
      </div>
    </div>
  );
}
