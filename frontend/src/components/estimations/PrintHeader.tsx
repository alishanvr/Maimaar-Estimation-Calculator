"use client";

import type { SheetTab } from "@/types";

const SHEET_TITLES: Record<SheetTab, string> = {
  input: "Input Sheet",
  recap: "Estimation Summary",
  detail: "Detail Sheet",
  fcpbs: "FCPBS — Full Cost & Price Breakdown",
  sal: "SAL — Sales Summary",
  boq: "BOQ — Bill of Quantities",
  jaf: "JAF — Job Acceptance Form",
};

interface PrintHeaderProps {
  quoteNumber: string;
  buildingName: string;
  revision: string;
  activeTab: SheetTab;
}

export default function PrintHeader({
  quoteNumber,
  buildingName,
  revision,
  activeTab,
}: PrintHeaderProps) {
  const today = new Date().toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });

  return (
    <div className="print-only">
      <div className="flex justify-between items-end border-b-2 border-gray-800 pb-2 mb-4">
        <div>
          <h1 className="text-base font-bold text-gray-900">
            Maimaar Estimation Calculator
          </h1>
          <p className="text-sm text-gray-600 mt-0.5">
            Quote: <span className="font-mono font-medium">{quoteNumber || "\u2014"}</span>
            {" \u00B7 "}
            Building: <span className="font-medium">{buildingName || "\u2014"}</span>
            {" \u00B7 "}
            Rev: <span className="font-mono">{revision || "0"}</span>
          </p>
        </div>
        <div className="text-right">
          <p className="text-sm font-semibold text-gray-900">
            {SHEET_TITLES[activeTab]}
          </p>
          <p className="text-xs text-gray-500 mt-0.5">{today}</p>
        </div>
      </div>
    </div>
  );
}
