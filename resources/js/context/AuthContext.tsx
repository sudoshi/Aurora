import React, { createContext, useState, useEffect, useCallback, useContext } from 'react';
import type { ReactNode } from 'react';
import axios from 'axios';
import type { User } from '../types';

interface AuthContextType {
  user: User | null;
  login: (userData: User, token: string) => void;
  logout: () => Promise<void>;
  updateUser: (userData: User) => void;
  isLoading: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

const TOKEN_KEY = 'auth_token';
const USER_KEY = 'user';

interface AuthProviderProps {
  children: ReactNode;
}

function AuthProvider({ children }: AuthProviderProps) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const setAuthToken = useCallback((token: string | null) => {
    if (token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      localStorage.setItem(TOKEN_KEY, token);
    } else {
      delete axios.defaults.headers.common['Authorization'];
      localStorage.removeItem(TOKEN_KEY);
    }
  }, []);

  // Initialize auth state
  useEffect(() => {
    const initializeAuth = async () => {
      try {
        const token = localStorage.getItem(TOKEN_KEY);
        const userData = localStorage.getItem(USER_KEY);

        if (token && userData) {
          setAuthToken(token);
          setUser(JSON.parse(userData) as User);

          // Verify token is still valid
          await axios.get('/api/user');
        }
      } catch (error) {
        console.error('Auth initialization error:', error);
        // Clear invalid auth state
        setAuthToken(null);
        localStorage.removeItem(USER_KEY);
        setUser(null);
      } finally {
        setIsLoading(false);
      }
    };

    initializeAuth();
  }, [setAuthToken]);

  const login = useCallback((userData: User, token: string) => {
    setAuthToken(token);
    localStorage.setItem(USER_KEY, JSON.stringify(userData));
    setUser(userData);
  }, [setAuthToken]);

  const updateUser = useCallback((userData: User) => {
    localStorage.setItem(USER_KEY, JSON.stringify(userData));
    setUser(userData);
  }, []);

  const logout = useCallback(async () => {
    try {
      // Attempt to logout on server
      await axios.post('/api/logout');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      // Clear auth state regardless of server response
      setAuthToken(null);
      localStorage.removeItem(USER_KEY);
      setUser(null);
    }
  }, [setAuthToken]);

  return (
    <AuthContext.Provider value={{ user, login, logout, updateUser, isLoading }}>
      {children}
    </AuthContext.Provider>
  );
}

function useAuth(): AuthContextType {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

export { AuthContext, AuthProvider, useAuth };
export type { AuthContextType };
