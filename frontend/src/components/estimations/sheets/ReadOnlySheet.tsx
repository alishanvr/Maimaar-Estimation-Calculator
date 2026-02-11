"use client";

import { useRef, useEffect, useState, type ReactNode } from "react";

interface ReadOnlySheetProps {
  isLoading: boolean;
  error: string | null;
  sheetLabel: string;
  children: ReactNode | ((height: number) => ReactNode);
}

/**
 * Shared wrapper for all output sheet components.
 * Handles ResizeObserver height measurement, loading spinner, and error states.
 *
 * Children can be:
 * - A render function `(height: number) => ReactNode` for Handsontable sheets
 *   that need an explicit pixel height.
 * - Regular ReactNode for card-based layouts (Recap, JAF) that scroll naturally.
 */
export default function ReadOnlySheet({
  isLoading,
  error,
  sheetLabel,
  children,
}: ReadOnlySheetProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const [containerHeight, setContainerHeight] = useState(0);

  useEffect(() => {
    const el = containerRef.current;
    if (!el) return;

    const observer = new ResizeObserver((entries) => {
      for (const entry of entries) {
        setContainerHeight(entry.contentRect.height);
      }
    });
    observer.observe(el);
    setContainerHeight(el.clientHeight);
    return () => observer.disconnect();
  }, [isLoading, error]);

  if (isLoading) {
    return (
      <div className="flex-1 flex items-center justify-center">
        <p className="text-gray-400 text-sm">Loading {sheetLabel}...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex-1 flex items-center justify-center">
        <p className="text-red-500 text-sm">{error}</p>
      </div>
    );
  }

  // Render function children (Handsontable sheets needing pixel height)
  if (typeof children === "function") {
    return (
      <div ref={containerRef} className="flex-1 overflow-hidden">
        {containerHeight > 0 && children(containerHeight)}
      </div>
    );
  }

  // Regular ReactNode children (card layouts like Recap, JAF)
  return (
    <div ref={containerRef} className="flex-1 overflow-auto">
      {children}
    </div>
  );
}
