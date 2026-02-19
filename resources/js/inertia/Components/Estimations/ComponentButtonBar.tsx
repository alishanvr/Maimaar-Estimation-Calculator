import { COMPONENT_CONFIGS, type ComponentType } from "./ComponentTableConfig";

interface ComponentButtonBarProps {
  activeComponents: Record<ComponentType, boolean>;
  onToggle: (type: ComponentType) => void;
  onScrollTo: (type: ComponentType) => void;
}

/**
 * A row of toggle buttons for building components (Openings, Accessories, Crane, Mezzanine, etc.).
 *
 * - Inactive: gray outline with "+" prefix — clicking adds the component.
 * - Active: indigo bg with checkmark — clicking scrolls to section. "x" removes with confirmation.
 */
export default function ComponentButtonBar({
  activeComponents,
  onToggle,
  onScrollTo,
}: ComponentButtonBarProps) {
  return (
    <div className="no-print flex items-center gap-2 px-1 py-2 border-b border-gray-200 bg-gray-50">
      <span className="text-xs font-medium text-gray-500 mr-1">
        Sections:
      </span>
      {COMPONENT_CONFIGS.map((config) => {
        const isActive = activeComponents[config.key];

        if (isActive) {
          return (
            <div key={config.key} className="flex items-center">
              <button
                type="button"
                onClick={() => onScrollTo(config.key)}
                className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-l-md bg-indigo-600 text-white hover:bg-indigo-700 transition"
              >
                <svg
                  className="w-3 h-3"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M5 13l4 4L19 7"
                  />
                </svg>
                {config.label}
              </button>
              <button
                type="button"
                onClick={() => {
                  if (
                    window.confirm(
                      `Remove ${config.label}? This will clear all ${config.label.toLowerCase()} data.`
                    )
                  ) {
                    onToggle(config.key);
                  }
                }}
                className="inline-flex items-center px-1.5 py-1 text-xs font-medium rounded-r-md bg-indigo-500 text-indigo-100 hover:bg-red-600 hover:text-white transition border-l border-indigo-400"
                title={`Remove ${config.label}`}
              >
                <svg
                  className="w-3 h-3"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          );
        }

        return (
          <button
            key={config.key}
            type="button"
            onClick={() => onToggle(config.key)}
            className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md border border-gray-300 text-gray-600 bg-white hover:bg-gray-100 hover:border-gray-400 transition"
          >
            <svg
              className="w-3 h-3"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 4v16m8-8H4"
              />
            </svg>
            {config.label}
          </button>
        );
      })}
    </div>
  );
}
