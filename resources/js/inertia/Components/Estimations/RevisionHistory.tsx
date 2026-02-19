import { useState, useEffect, useRef } from "react";
import { router } from "@inertiajs/react";
import { getRevisions } from "../../lib/estimations";
import type { RevisionEntry, EstimationStatus } from "../../types";

const STATUS_BADGE: Record<EstimationStatus, string> = {
  draft: "bg-gray-100 text-gray-700",
  calculated: "bg-green-100 text-green-700",
  finalized: "bg-primary/15 text-primary",
};

interface RevisionHistoryProps {
  estimationId: number;
  currentRevision: string | null;
}

export default function RevisionHistory({
  estimationId,
  currentRevision,
}: RevisionHistoryProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [revisions, setRevisions] = useState<RevisionEntry[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!isOpen) return;
    setIsLoading(true);
    getRevisions(estimationId)
      .then(setRevisions)
      .catch(() => setRevisions([]))
      .finally(() => setIsLoading(false));
  }, [isOpen, estimationId]);

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (
        dropdownRef.current &&
        !dropdownRef.current.contains(e.target as Node)
      ) {
        setIsOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const formatNumber = (value: number | null, decimals = 2): string => {
    if (value === null || value === undefined) return "\u2014";
    return value.toLocaleString("en-US", {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    });
  };

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition"
      >
        {currentRevision || "R00"}
        <svg
          className={`w-3 h-3 transition-transform ${isOpen ? "rotate-180" : ""}`}
          fill="none"
          stroke="currentColor"
          strokeWidth={2}
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M19.5 8.25l-7.5 7.5-7.5-7.5"
          />
        </svg>
      </button>

      {isOpen && (
        <div className="absolute top-full left-0 mt-1 z-50 w-72 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
          <div className="px-3 py-2 bg-gray-50 border-b border-gray-200">
            <span className="text-xs font-medium text-gray-500">
              Revision History
            </span>
          </div>

          {isLoading && (
            <div className="px-3 py-4 text-center text-xs text-gray-400">
              Loading...
            </div>
          )}

          {!isLoading && revisions.length === 0 && (
            <div className="px-3 py-4 text-center text-xs text-gray-400">
              No revision history
            </div>
          )}

          {!isLoading && revisions.length > 0 && (
            <div className="max-h-60 overflow-y-auto divide-y divide-gray-100">
              {revisions.map((rev) => (
                <button
                  key={rev.id}
                  onClick={() => {
                    setIsOpen(false);
                    if (rev.id !== estimationId) {
                      router.visit(`/v2/estimations/${rev.id}`);
                    }
                  }}
                  className={`w-full text-left px-3 py-2 text-xs hover:bg-gray-50 transition flex items-center justify-between ${
                    rev.is_current ? "bg-indigo-50" : ""
                  }`}
                >
                  <div className="flex items-center gap-2">
                    <span className="font-mono font-medium">
                      {rev.revision_no || "R00"}
                    </span>
                    <span
                      className={`inline-block px-1.5 py-0.5 rounded-full text-[10px] font-medium ${STATUS_BADGE[rev.status]}`}
                    >
                      {rev.status}
                    </span>
                    {rev.is_current && (
                      <span className="text-[10px] text-indigo-600 font-medium">
                        (current)
                      </span>
                    )}
                  </div>
                  <div className="text-gray-400 text-right">
                    <span>{formatNumber(rev.total_weight_mt)} MT</span>
                  </div>
                </button>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
