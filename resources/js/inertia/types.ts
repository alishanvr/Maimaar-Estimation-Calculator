export interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    company_name: string | null;
}

export interface PageProps {
    auth: {
        user: User | null;
    };
    flash: {
        success: string | null;
        error: string | null;
    };
}
