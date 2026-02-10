"use client";

import { useAuth } from "@/contexts/AuthContext";

export default function DashboardPage() {
  const { user } = useAuth();

  return (
    <div>
      <div className="mb-8">
        <h2 className="text-2xl font-bold text-gray-900">
          Welcome back, {user?.name}
        </h2>
        <p className="text-gray-500 mt-1">
          Maimaar Estimation Calculator Dashboard
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 className="text-sm font-medium text-gray-500">Your Email</h3>
          <p className="mt-2 text-lg font-semibold text-gray-900">
            {user?.email}
          </p>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 className="text-sm font-medium text-gray-500">Estimations</h3>
          <p className="mt-2 text-lg font-semibold text-gray-900">--</p>
          <p className="text-sm text-gray-400 mt-1">Coming soon</p>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 className="text-sm font-medium text-gray-500">Quick Actions</h3>
          <a
            href="/estimations"
            className="mt-3 inline-block text-sm text-blue-600 hover:text-blue-800 font-medium transition"
          >
            View Estimations
          </a>
        </div>
      </div>
    </div>
  );
}
