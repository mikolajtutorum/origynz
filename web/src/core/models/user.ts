export interface User {
  id: string;
  name: string;
  first_name: string | null;
  middle_name: string | null;
  last_name: string | null;
  email: string;
  email_verified_at?: string | null;
  preferred_locale?: string | null;
  two_factor_enabled?: boolean;
  is_super_admin?: boolean;
  created_at?: string | null;
}
