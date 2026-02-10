"use client";

import type { SheetTab } from "@/types";

const SHEET_LABELS: Record<Exclude<SheetTab, "input">, string> = {
  recap: "Recap (Summary)",
  detail: "Detail (Bill of Materials)",
  fcpbs: "FCPBS (Financial Category Breakdown)",
  sal: "SAL (Sales Analysis)",
  boq: "BOQ (Bill of Quantities)",
  jaf: "JAF (Job Acceptance Form)",
};

interface SheetTabProps {
  sheet: Exclude<SheetTab, "input">;
  isCalculated: boolean;
}

export default function SheetTabPlaceholder({
  sheet,
  isCalculated,
}: SheetTabProps) {
  return (
    <div className="flex-1 flex items-center justify-center bg-white">
      <div className="text-center p-8">
        {isCalculated ? (
          <>
            <div className="text-green-500 text-4xl mb-3">&#10003;</div>
            <h3 className="text-lg font-medium text-gray-900 mb-1">
              {SHEET_LABELS[sheet]}
            </h3>
            <p className="text-gray-500 text-sm">
              Sheet data is available. Full rendering coming in the next
              iteration.
            </p>
          </>
        ) : (
          <>
            <div className="text-gray-300 text-4xl mb-3">&#9888;</div>
            <h3 className="text-lg font-medium text-gray-900 mb-1">
              {SHEET_LABELS[sheet]}
            </h3>
            <p className="text-gray-500 text-sm">
              Click <strong>Calculate</strong> to generate this sheet&apos;s
              data.
            </p>
          </>
        )}
      </div>
    </div>
  );
}
