"use client";

import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useAuth } from "@/contexts/AuthContext";
import { useBranding } from "@/contexts/BrandingContext";
import { createEstimation } from "@/lib/estimations";
import { useState } from "react";

export default function Navbar() {
  const { user, logout } = useAuth();
  const { branding } = useBranding();
  const router = useRouter();
  const [isCreating, setIsCreating] = useState(false);

  const handleLogout = async () => {
    await logout();
  };

  const handleNewEstimation = async () => {
    setIsCreating(true);
    try {
      const estimation = await createEstimation({
        building_name: "New Building",
      });
      router.push(`/estimations/${estimation.id}`);
    } catch {
      alert("Failed to create estimation.");
    } finally {
      setIsCreating(false);
    }
  };

  return (
    <nav className="bg-white border-b border-gray-200">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          <div className="flex items-center gap-6">
            <Link href="/" className="flex items-center gap-2">
              {branding.logo_url ? (
                <Image
                  src={branding.logo_url}
                  alt={branding.company_name}
                  width={120}
                  height={32}
                  className="h-8 w-auto object-contain"
                  unoptimized
                />
              ) : (
                <span className="text-xl font-bold text-gray-900">
                  {branding.company_name}
                </span>
              )}
            </Link>
            <div className="hidden sm:flex items-center gap-4">
              <Link
                href="/"
                className="text-sm text-gray-600 hover:text-gray-900 transition"
              >
                Dashboard
              </Link>
              <Link
                href="/estimations"
                className="text-sm text-gray-600 hover:text-gray-900 transition"
              >
                Estimations
              </Link>
              <Link
                href="/reports"
                className="text-sm text-gray-600 hover:text-gray-900 transition"
              >
                Reports
              </Link>
            </div>
          </div>

          <div className="flex items-center gap-4">
            <button
              onClick={handleNewEstimation}
              disabled={isCreating}
              className="hidden sm:block bg-primary text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-primary/80 transition disabled:opacity-50"
            >
              {isCreating ? "Creating..." : "+ New"}
            </button>
            {user && (
              <span className="text-sm text-gray-600">{user.name}</span>
            )}
            <button
              onClick={handleLogout}
              className="text-sm text-red-600 hover:text-red-800 font-medium transition"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </nav>
  );
}
