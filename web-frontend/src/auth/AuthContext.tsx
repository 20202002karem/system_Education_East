import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { apiRequest, clearToken, getToken, registerUnauthorizedHandler, setToken } from '../lib/apiClient';
import type { Role, User } from '../lib/types';

type AuthStatus = 'initial' | 'loading' | 'authenticated' | 'unauthenticated';

interface AuthContextValue {
  status: AuthStatus;
  user: User | null;
  error: string | null;
  login: (loginIdentifier: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  hasRole: (...roles: Role[]) => boolean;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [status, setStatus] = useState<AuthStatus>('initial');
  const [user, setUser] = useState<User | null>(null);
  const [error, setError] = useState<string | null>(null);

  const handleUnauthenticated = useCallback(() => {
    clearToken();
    setUser(null);
    setStatus('unauthenticated');
  }, []);

  useEffect(() => {
    registerUnauthorizedHandler(handleUnauthenticated);
  }, [handleUnauthenticated]);

  useEffect(() => {
    const token = getToken();
    if (!token) {
      setStatus('unauthenticated');
      return;
    }

    setStatus('loading');
    apiRequest<User>('/auth/me')
      .then((me) => {
        setUser(me);
        setStatus('authenticated');
      })
      .catch(() => {
        handleUnauthenticated();
      });
  }, [handleUnauthenticated]);

  const login = useCallback(async (loginIdentifier: string, password: string): Promise<void> => {
    setError(null);
    setStatus('loading');

    try {
      const result = await apiRequest<{
        access_token?: string;
        token_type?: string;
        expires_in?: number;
      }>('/auth/login', {
        method: 'POST',
        body: {
          login_identifier: loginIdentifier,
          password,
        },
      });

      if (!result.access_token) {
        throw new Error('استجابة دخول غير متوقعة');
      }

      setToken(result.access_token);

      const me = await apiRequest<User>('/auth/me');
      setUser(me);
      setStatus('authenticated');
    } catch (e) {
      setStatus('unauthenticated');
      setError(e instanceof Error ? e.message : 'فشل تسجيل الدخول');
      throw e;
    }
  }, []);

  const logout = useCallback(async () => {
    try {
      await apiRequest('/auth/logout', { method: 'POST' });
    } finally {
      handleUnauthenticated();
    }
  }, [handleUnauthenticated]);

  const hasRole = useCallback((...roles: Role[]) => !!user && roles.includes(user.role), [user]);

  const value = useMemo(
    () => ({ status, user, error, login, logout, hasRole }),
    [status, user, error, login, logout, hasRole]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
