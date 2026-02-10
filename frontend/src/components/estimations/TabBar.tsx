"use client";

import { SHEET_TABS, type SheetTab } from "@/types";

interface TabBarProps {
  activeTab: SheetTab;
  onTabChange: (tab: SheetTab) => void;
  isCalculated: boolean;
}

export default function TabBar({
  activeTab,
  onTabChange,
  isCalculated,
}: TabBarProps) {
  return (
    <div className="flex items-center gap-0.5 bg-gray-200 border-t border-gray-300 px-2 py-1">
      {SHEET_TABS.map((tab) => {
        const isActive = activeTab === tab.key;
        const isDisabled = tab.key !== "input" && !isCalculated;

        return (
          <button
            key={tab.key}
            onClick={() => onTabChange(tab.key)}
            disabled={isDisabled}
            className={`px-4 py-1.5 text-xs font-medium border border-b-0 rounded-t-md transition ${
              isActive
                ? "bg-white text-gray-900 border-gray-300 -mb-px z-10"
                : isDisabled
                  ? "bg-gray-100 text-gray-400 border-transparent cursor-not-allowed"
                  : "bg-gray-100 text-gray-600 border-transparent hover:bg-gray-50 hover:text-gray-800"
            }`}
          >
            {tab.label}
          </button>
        );
      })}
    </div>
  );
}
