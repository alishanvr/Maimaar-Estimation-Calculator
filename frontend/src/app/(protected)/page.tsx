"use client";

import Link from "next/link";
import { useAuth } from "@/contexts/AuthContext";
import { useEstimations } from "@/hooks/useEstimations";

export default function DashboardPage() {
  const { user } = useAuth();
  const { estimations, isLoading } = useEstimations();

  const draftCount = estimations.filter((e) => e.status === "draft").length;
  const calculatedCount = estimations.filter(
    (e) => e.status === "calculated"
  ).length;

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
          <h3 className="text-sm font-medium text-gray-500">
            Total Estimations
          </h3>
          <p className="mt-2 text-3xl font-bold text-gray-900">
            {isLoading ? "..." : estimations.length}
          </p>
          <p className="text-sm text-gray-400 mt-1">
            {draftCount} draft, {calculatedCount} calculated
          </p>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 className="text-sm font-medium text-gray-500">Your Email</h3>
          <p className="mt-2 text-lg font-semibold text-gray-900">
            {user?.email}
          </p>
        </div>

        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 className="text-sm font-medium text-gray-500">Quick Actions</h3>
          <div className="mt-3 flex flex-col gap-2">
            <Link
              href="/estimations"
              className="text-sm text-blue-600 hover:text-blue-800 font-medium transition"
            >
              View Estimations &rarr;
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
