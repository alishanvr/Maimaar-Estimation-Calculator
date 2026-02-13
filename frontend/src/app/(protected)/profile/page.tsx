"use client";

import { useState, FormEvent } from "react";
import { useAuth } from "@/contexts/AuthContext";
import { updateProfile, changePassword } from "@/lib/profile";
import type { UpdateProfileData, ChangePasswordData } from "@/lib/profile";

export default function ProfilePage() {
  const { user, refreshUser } = useAuth();

  // Profile form state
  const [profileData, setProfileData] = useState<UpdateProfileData>({
    name: user?.name ?? "",
    email: user?.email ?? "",
    company_name: user?.company_name ?? "",
    phone: user?.phone ?? "",
  });
  const [profileSuccess, setProfileSuccess] = useState("");
  const [profileErrors, setProfileErrors] = useState<Record<string, string[]>>(
    {}
  );
  const [isUpdatingProfile, setIsUpdatingProfile] = useState(false);

  // Password form state
  const [passwordData, setPasswordData] = useState<ChangePasswordData>({
    current_password: "",
    password: "",
    password_confirmation: "",
  });
  const [passwordSuccess, setPasswordSuccess] = useState("");
  const [passwordErrors, setPasswordErrors] = useState<
    Record<string, string[]>
  >({});
  const [isChangingPassword, setIsChangingPassword] = useState(false);

  const handleProfileSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setProfileErrors({});
    setProfileSuccess("");
    setIsUpdatingProfile(true);

    try {
      await updateProfile(profileData);
      await refreshUser();
      setProfileSuccess("Profile updated successfully.");
    } catch (error: unknown) {
      const err = error as { response?: { data?: { errors?: Record<string, string[]> } } };
      if (err.response?.data?.errors) {
        setProfileErrors(err.response.data.errors);
      } else {
        setProfileErrors({ general: ["Failed to update profile."] });
      }
    } finally {
      setIsUpdatingProfile(false);
    }
  };

  const handlePasswordSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setPasswordErrors({});
    setPasswordSuccess("");
    setIsChangingPassword(true);

    try {
      await changePassword(passwordData);
      setPasswordSuccess("Password changed successfully.");
      setPasswordData({
        current_password: "",
        password: "",
        password_confirmation: "",
      });
    } catch (error: unknown) {
      const err = error as { response?: { data?: { errors?: Record<string, string[]> } } };
      if (err.response?.data?.errors) {
        setPasswordErrors(err.response.data.errors);
      } else {
        setPasswordErrors({ general: ["Failed to change password."] });
      }
    } finally {
      setIsChangingPassword(false);
    }
  };

  const inputClassName =
    "w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition";

  return (
    <div className="max-w-2xl mx-auto">
      <div className="mb-8">
        <h2 className="text-2xl font-bold text-gray-900">Profile Settings</h2>
        <p className="text-gray-500 mt-1">
          Manage your account information and password
        </p>
      </div>

      {/* Profile Information */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">
          Profile Information
        </h3>

        <form onSubmit={handleProfileSubmit} className="space-y-4">
          {profileSuccess && (
            <div className="bg-green-50 text-green-700 text-sm rounded-lg p-3 border border-green-200">
              {profileSuccess}
            </div>
          )}
          {profileErrors.general && (
            <div className="bg-red-50 text-red-700 text-sm rounded-lg p-3 border border-red-200">
              {profileErrors.general[0]}
            </div>
          )}

          <div>
            <label
              htmlFor="name"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              Name <span className="text-red-500">*</span>
            </label>
            <input
              id="name"
              type="text"
              required
              value={profileData.name}
              onChange={(e) =>
                setProfileData({ ...profileData, name: e.target.value })
              }
              className={inputClassName}
              placeholder="Your name"
            />
            {profileErrors.name && (
              <p className="mt-1 text-sm text-red-600">
                {profileErrors.name[0]}
              </p>
            )}
          </div>

          <div>
            <label
              htmlFor="email"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              Email <span className="text-red-500">*</span>
            </label>
            <input
              id="email"
              type="email"
              required
              value={profileData.email}
              onChange={(e) =>
                setProfileData({ ...profileData, email: e.target.value })
              }
              className={inputClassName}
              placeholder="you@example.com"
            />
            {profileErrors.email && (
              <p className="mt-1 text-sm text-red-600">
                {profileErrors.email[0]}
              </p>
            )}
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label
                htmlFor="company_name"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Company Name
              </label>
              <input
                id="company_name"
                type="text"
                value={profileData.company_name ?? ""}
                onChange={(e) =>
                  setProfileData({
                    ...profileData,
                    company_name: e.target.value || null,
                  })
                }
                className={inputClassName}
                placeholder="Your company"
              />
            </div>

            <div>
              <label
                htmlFor="phone"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Phone
              </label>
              <input
                id="phone"
                type="text"
                value={profileData.phone ?? ""}
                onChange={(e) =>
                  setProfileData({
                    ...profileData,
                    phone: e.target.value || null,
                  })
                }
                className={inputClassName}
                placeholder="Your phone number"
              />
            </div>
          </div>

          <div className="flex justify-end pt-2">
            <button
              type="submit"
              disabled={isUpdatingProfile}
              className="bg-primary text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-primary/80 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isUpdatingProfile ? "Saving..." : "Save Changes"}
            </button>
          </div>
        </form>
      </div>

      {/* Change Password */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">
          Change Password
        </h3>

        <form onSubmit={handlePasswordSubmit} className="space-y-4">
          {passwordSuccess && (
            <div className="bg-green-50 text-green-700 text-sm rounded-lg p-3 border border-green-200">
              {passwordSuccess}
            </div>
          )}
          {passwordErrors.general && (
            <div className="bg-red-50 text-red-700 text-sm rounded-lg p-3 border border-red-200">
              {passwordErrors.general[0]}
            </div>
          )}

          <div>
            <label
              htmlFor="current_password"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              Current Password <span className="text-red-500">*</span>
            </label>
            <input
              id="current_password"
              type="password"
              required
              value={passwordData.current_password}
              onChange={(e) =>
                setPasswordData({
                  ...passwordData,
                  current_password: e.target.value,
                })
              }
              className={inputClassName}
              placeholder="Enter current password"
            />
            {passwordErrors.current_password && (
              <p className="mt-1 text-sm text-red-600">
                {passwordErrors.current_password[0]}
              </p>
            )}
          </div>

          <div>
            <label
              htmlFor="new_password"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              New Password <span className="text-red-500">*</span>
            </label>
            <input
              id="new_password"
              type="password"
              required
              value={passwordData.password}
              onChange={(e) =>
                setPasswordData({
                  ...passwordData,
                  password: e.target.value,
                })
              }
              className={inputClassName}
              placeholder="Minimum 8 characters"
            />
            {passwordErrors.password && (
              <p className="mt-1 text-sm text-red-600">
                {passwordErrors.password[0]}
              </p>
            )}
          </div>

          <div>
            <label
              htmlFor="password_confirmation"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              Confirm New Password <span className="text-red-500">*</span>
            </label>
            <input
              id="password_confirmation"
              type="password"
              required
              value={passwordData.password_confirmation}
              onChange={(e) =>
                setPasswordData({
                  ...passwordData,
                  password_confirmation: e.target.value,
                })
              }
              className={inputClassName}
              placeholder="Re-enter new password"
            />
          </div>

          <div className="flex justify-end pt-2">
            <button
              type="submit"
              disabled={isChangingPassword}
              className="bg-primary text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-primary/80 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isChangingPassword ? "Changing..." : "Change Password"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
