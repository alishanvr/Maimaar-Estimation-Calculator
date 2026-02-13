import api from "@/lib/api";

export interface UpdateProfileData {
  name: string;
  email: string;
  company_name?: string | null;
  phone?: string | null;
}

export interface ChangePasswordData {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export async function updateProfile(data: UpdateProfileData) {
  const response = await api.put("/user/profile", data);
  return response.data;
}

export async function changePassword(data: ChangePasswordData) {
  const response = await api.put("/user/password", data);
  return response.data;
}
