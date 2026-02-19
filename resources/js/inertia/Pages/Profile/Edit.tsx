import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import { useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
        company_name: string | null;
        phone: string | null;
    };
}

export default function Edit({ user }: Props) {
    const profileForm = useForm({
        name: user.name,
        email: user.email,
        company_name: user.company_name ?? '',
        phone: user.phone ?? '',
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleProfileSubmit = (e: FormEvent) => {
        e.preventDefault();
        profileForm.put('/v2/profile', {
            preserveScroll: true,
        });
    };

    const handlePasswordSubmit = (e: FormEvent) => {
        e.preventDefault();
        passwordForm.put('/v2/profile/password', {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    };

    const inputClassName =
        'w-full rounded-lg border border-gray-300 px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition';

    return (
        <AuthenticatedLayout title="Profile Settings">
            <div className="max-w-2xl mx-auto">
                <div className="mb-8">
                    <h2 className="text-2xl font-bold text-gray-900">
                        Profile Settings
                    </h2>
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
                        {profileForm.recentlySuccessful && (
                            <div className="bg-green-50 text-green-700 text-sm rounded-lg p-3 border border-green-200">
                                Profile updated successfully.
                            </div>
                        )}

                        <div>
                            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                                Name <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="name"
                                type="text"
                                required
                                value={profileForm.data.name}
                                onChange={(e) => profileForm.setData('name', e.target.value)}
                                className={inputClassName}
                                placeholder="Your name"
                            />
                            {profileForm.errors.name && (
                                <p className="mt-1 text-sm text-red-600">{profileForm.errors.name}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
                                Email <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="email"
                                type="email"
                                required
                                value={profileForm.data.email}
                                onChange={(e) => profileForm.setData('email', e.target.value)}
                                className={inputClassName}
                                placeholder="you@example.com"
                            />
                            {profileForm.errors.email && (
                                <p className="mt-1 text-sm text-red-600">{profileForm.errors.email}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label htmlFor="company_name" className="block text-sm font-medium text-gray-700 mb-1">
                                    Company Name
                                </label>
                                <input
                                    id="company_name"
                                    type="text"
                                    value={profileForm.data.company_name}
                                    onChange={(e) => profileForm.setData('company_name', e.target.value)}
                                    className={inputClassName}
                                    placeholder="Your company"
                                />
                            </div>
                            <div>
                                <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1">
                                    Phone
                                </label>
                                <input
                                    id="phone"
                                    type="text"
                                    value={profileForm.data.phone}
                                    onChange={(e) => profileForm.setData('phone', e.target.value)}
                                    className={inputClassName}
                                    placeholder="Your phone number"
                                />
                            </div>
                        </div>

                        <div className="flex justify-end pt-2">
                            <button
                                type="submit"
                                disabled={profileForm.processing}
                                className="bg-primary text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-primary/80 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {profileForm.processing ? 'Saving...' : 'Save Changes'}
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
                        {passwordForm.recentlySuccessful && (
                            <div className="bg-green-50 text-green-700 text-sm rounded-lg p-3 border border-green-200">
                                Password changed successfully.
                            </div>
                        )}

                        <div>
                            <label htmlFor="current_password" className="block text-sm font-medium text-gray-700 mb-1">
                                Current Password <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="current_password"
                                type="password"
                                required
                                value={passwordForm.data.current_password}
                                onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                className={inputClassName}
                                placeholder="Enter current password"
                            />
                            {passwordForm.errors.current_password && (
                                <p className="mt-1 text-sm text-red-600">{passwordForm.errors.current_password}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="new_password" className="block text-sm font-medium text-gray-700 mb-1">
                                New Password <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="new_password"
                                type="password"
                                required
                                value={passwordForm.data.password}
                                onChange={(e) => passwordForm.setData('password', e.target.value)}
                                className={inputClassName}
                                placeholder="Minimum 8 characters"
                            />
                            {passwordForm.errors.password && (
                                <p className="mt-1 text-sm text-red-600">{passwordForm.errors.password}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700 mb-1">
                                Confirm New Password <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                required
                                value={passwordForm.data.password_confirmation}
                                onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                className={inputClassName}
                                placeholder="Re-enter new password"
                            />
                        </div>

                        <div className="flex justify-end pt-2">
                            <button
                                type="submit"
                                disabled={passwordForm.processing}
                                className="bg-primary text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-primary/80 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {passwordForm.processing ? 'Changing...' : 'Change Password'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
