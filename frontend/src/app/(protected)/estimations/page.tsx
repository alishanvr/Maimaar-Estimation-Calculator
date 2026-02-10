"use client";

export default function EstimationsPage() {
  return (
    <div>
      <div className="mb-8">
        <h2 className="text-2xl font-bold text-gray-900">Estimations</h2>
        <p className="text-gray-500 mt-1">
          Manage your construction estimations
        </p>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
        <div className="text-gray-400 text-5xl mb-4">&#9634;</div>
        <h3 className="text-lg font-medium text-gray-900">
          No estimations yet
        </h3>
        <p className="text-gray-500 mt-2">
          Your estimations will appear here once you create them.
        </p>
      </div>
    </div>
  );
}
