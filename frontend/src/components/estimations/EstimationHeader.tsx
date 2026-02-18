"use client";

import Link from "next/link";
import RevisionHistory from "@/components/estimations/RevisionHistory";
import { useCurrency } from "@/hooks/useCurrency";
import type { Estimation, EstimationStatus } from "@/types";

const STATUS_BADGE: Record<EstimationStatus, string> = {
  draft: "bg-gray-100 text-gray-700",
  calculated: "bg-green-100 text-green-700",
  finalized: "bg-primary/15 text-primary",
};

interface EstimationHeaderProps {
  estimation: Estimation;
  isSaving: boolean;
  isCalculating: boolean;
  onSave: () => void;
  onCalculate: () => void;
  onFillTestData?: () => void;
  onClone?: () => void;
  onCreateRevision?: () => void;
  onFinalize?: () => void;
  onUnlock?: () => void;
  onImportCsv?: () => void;
}

export default function EstimationHeader({
  estimation,
  isSaving,
  isCalculating,
  onSave,
  onCalculate,
  onFillTestData,
  onClone,
  onCreateRevision,
  onFinalize,
  onUnlock,
  onImportCsv,
}: EstimationHeaderProps) {
  const isFinalized = estimation.status === "finalized";
  const { format } = useCurrency();

  const formatNumber = (value: number | null, decimals = 2): string => {
    if (value === null || value === undefined) return "\u2014";
    return value.toLocaleString("en-US", {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    });
  };

  return (
    <div className="no-print bg-white border-b border-gray-200 px-4 py-3 relative z-50">
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
            <RevisionHistory
              estimationId={estimation.id}
              currentRevision={estimation.revision_no}
            />
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
              {format(estimation.total_price_aed, 0)}
            </p>
          </div>
        </div>

        {/* Right: Actions */}
        <div className="flex items-center gap-2">
          {isSaving && (
            <span className="text-xs text-gray-400 mr-2">Saving...</span>
          )}
          {onClone && (
            <button
              onClick={onClone}
              disabled={isCalculating}
              className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50"
            >
              Clone
            </button>
          )}
          {onCreateRevision && (
            <button
              onClick={onCreateRevision}
              disabled={isCalculating}
              className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50"
            >
              New Rev
            </button>
          )}
          {onImportCsv && !isFinalized && (
            <button
              onClick={onImportCsv}
              disabled={isCalculating}
              className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition disabled:opacity-50"
            >
              Import CSV
            </button>
          )}
          {onFillTestData && !isFinalized && (
            <button
              onClick={onFillTestData}
              disabled={isCalculating}
              className="px-3 py-1.5 text-sm border border-amber-400 rounded-lg text-amber-700 bg-amber-50 hover:bg-amber-100 transition disabled:opacity-50"
            >
              Fill Test Data
            </button>
          )}
          {!isFinalized && (
            <button
              onClick={onSave}
              disabled={isSaving}
              className="px-3 py-1.5 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition disabled:opacity-50"
            >
              Save
            </button>
          )}
          {!isFinalized && (
            <button
              onClick={onCalculate}
              disabled={isCalculating}
              className="px-3 py-1.5 text-sm bg-primary text-white rounded-lg font-medium hover:bg-primary/80 transition disabled:opacity-50"
            >
              {isCalculating ? "Calculating..." : "Calculate"}
            </button>
          )}
          {onFinalize && estimation.status === "calculated" && (
            <button
              onClick={onFinalize}
              className="px-3 py-1.5 text-sm bg-primary text-white rounded-lg font-medium hover:bg-primary/80 transition"
            >
              Finalize
            </button>
          )}
          {onUnlock && isFinalized && (
            <button
              onClick={onUnlock}
              className="px-3 py-1.5 text-sm border border-amber-400 rounded-lg text-amber-700 bg-amber-50 hover:bg-amber-100 transition"
            >
              Unlock
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
