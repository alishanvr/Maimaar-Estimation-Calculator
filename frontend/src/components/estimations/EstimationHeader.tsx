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
  onPrint?: () => void;
  onFillTestData?: () => void;
}

export default function EstimationHeader({
  estimation,
  isSaving,
  isCalculating,
  onSave,
  onCalculate,
  onPrint,
  onFillTestData,
}: EstimationHeaderProps) {
  const formatNumber = (value: number | null, decimals = 2): string => {
    if (value === null || value === undefined) return "\u2014";
    return value.toLocaleString("en-US", {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    });
  };

  return (
    <div className="no-print bg-white border-b border-gray-200 px-4 py-3">
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
          {onFillTestData && (
            <button
              onClick={onFillTestData}
              disabled={isCalculating}
              className="px-3 py-1.5 text-sm border border-amber-400 rounded-lg text-amber-700 bg-amber-50 hover:bg-amber-100 transition disabled:opacity-50"
            >
              Fill Test Data
            </button>
          )}
          <button
            onClick={onSave}
            disabled={isSaving}
            className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition disabled:opacity-50"
          >
            Save
          </button>
          {onPrint && (
            <button
              onClick={onPrint}
              disabled={isCalculating}
              title="Print / Save as PDF"
              className="p-1.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50"
            >
              <svg
                className="w-4 h-4"
                fill="none"
                stroke="currentColor"
                strokeWidth={1.5}
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 7.281H5.25"
                />
              </svg>
            </button>
          )}
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
