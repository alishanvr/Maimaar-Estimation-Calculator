"use client";

import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useAuth } from "@/contexts/AuthContext";
import { useBranding } from "@/contexts/BrandingContext";
import { createEstimation } from "@/lib/estimations";
import { useState, useRef, useEffect } from "react";

export default function Navbar() {
  const { user, logout } = useAuth();
  const { branding } = useBranding();
  const router = useRouter();
  const [isCreating, setIsCreating] = useState(false);
  const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);
  const userMenuRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (
        userMenuRef.current &&
        !userMenuRef.current.contains(event.target as Node)
      ) {
        setIsUserMenuOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const handleLogout = async () => {
    setIsUserMenuOpen(false);
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

            {/* User dropdown */}
            <div className="relative" ref={userMenuRef}>
              <button
                onClick={() => setIsUserMenuOpen(!isUserMenuOpen)}
                className="flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900 transition rounded-lg px-2 py-1.5 hover:bg-gray-50"
              >
                <div className="w-7 h-7 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-semibold">
                  {user?.name?.charAt(0)?.toUpperCase() ?? "U"}
                </div>
                <span className="hidden sm:inline">{user?.name}</span>
                <svg
                  className={`w-4 h-4 text-gray-400 transition-transform ${isUserMenuOpen ? "rotate-180" : ""}`}
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  strokeWidth={2}
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </button>

              {isUserMenuOpen && (
                <div className="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                  <div className="px-3 py-2 border-b border-gray-100">
                    <p className="text-sm font-medium text-gray-900 truncate">
                      {user?.name}
                    </p>
                    <p className="text-xs text-gray-500 truncate">
                      {user?.email}
                    </p>
                  </div>
                  <Link
                    href="/profile"
                    onClick={() => setIsUserMenuOpen(false)}
                    className="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
                  >
                    Profile Settings
                  </Link>
                  <button
                    onClick={handleLogout}
                    className="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition"
                  >
                    Logout
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </nav>
  );
}
