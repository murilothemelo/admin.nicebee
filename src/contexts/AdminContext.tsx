import React, { createContext, useContext, useReducer, useEffect } from 'react';
import { User, AuthState } from '../types';

interface AdminContextType {
  auth: AuthState;
  login: (email: string, senha: string) => Promise<void>;
  logout: () => void;
  resetPassword: (email: string) => Promise<any>;
  changePassword: (currentPassword: string, newPassword: string) => Promise<any>;
  updateProfile: (profileData: { nome: string; email: string }) => Promise<any>;
}

const AdminContext = createContext<AdminContextType | undefined>(undefined);

type AuthAction = 
  | { type: 'LOGIN_START' }
  | { type: 'LOGIN_SUCCESS'; payload: { user: User; token: string } }
  | { type: 'LOGIN_ERROR' }
  | { type: 'LOGOUT' }
  | { type: 'RESTORE_SESSION'; payload: { user: User; token: string } };

const authReducer = (state: AuthState, action: AuthAction): AuthState => {
  switch (action.type) {
    case 'LOGIN_START':
      return { ...state, isLoading: true };
    case 'LOGIN_SUCCESS':
      return {
        user: action.payload.user,
        token: action.payload.token,
        isLoading: false,
        isAuthenticated: true
      };
    case 'LOGIN_ERROR':
      return {
        user: null,
        token: null,
        isLoading: false,
        isAuthenticated: false
      };
    case 'LOGOUT':
      return {
        user: null,
        token: null,
        isLoading: false,
        isAuthenticated: false
      };
    case 'RESTORE_SESSION':
      return {
        user: action.payload.user,
        token: action.payload.token,
        isLoading: false,
        isAuthenticated: true
      };
    default:
      return state;
  }
};

const initialState: AuthState = {
  user: null,
  token: null,
  isLoading: true,
  isAuthenticated: false
};

export function AdminProvider({ children }: { children: React.ReactNode }) {
  const [auth, dispatch] = useReducer(authReducer, initialState);

  useEffect(() => {
    const token = localStorage.getItem('admin_token');
    const userStr = localStorage.getItem('admin_user');
    
    if (token && userStr) {
      try {
        const user = JSON.parse(userStr);
        // Validar se o token não expirou (verificação simples)
        const tokenData = JSON.parse(atob(token.split('.')[1] || ''));
        if (tokenData.exp && tokenData.exp * 1000 > Date.now()) {
          dispatch({ type: 'RESTORE_SESSION', payload: { user, token } });
        } else {
          localStorage.removeItem('admin_token');
          localStorage.removeItem('admin_user');
          dispatch({ type: 'LOGIN_ERROR' });
        }
      } catch {
        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_user');
        dispatch({ type: 'LOGIN_ERROR' });
      }
    } else {
      dispatch({ type: 'LOGIN_ERROR' });
    }
  }, []);

  const login = async (email: string, senha: string) => {
    dispatch({ type: 'LOGIN_START' });
    try {
      const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';
      const response = await fetch(`${API_BASE_URL}/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, senha })
      });

      if (!response.ok) {
        const errorText = await response.text();
        let errorMessage = 'Credenciais inválidas';
        
        try {
          const errorJson = JSON.parse(errorText);
          errorMessage = errorJson.message || errorMessage;
        } catch {
          // Se não for JSON, usar mensagem padrão
        }
        
        throw new Error(errorMessage);
      }
      
      const result = await response.json();

      if (result.success) {
        const { user, token } = result;
        localStorage.setItem('admin_token', token);
        localStorage.setItem('admin_user', JSON.stringify(user));
        dispatch({ type: 'LOGIN_SUCCESS', payload: { user, token } });
      } else {
        throw new Error(result.message || 'Erro no login');
      }
    } catch (error) {
      dispatch({ type: 'LOGIN_ERROR' });
      throw error;
    }
  };


  const resetPassword = async (email: string) => {
    const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';
    const response = await fetch(`${API_BASE_URL}/auth/reset-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });

    if (!response.ok) throw new Error('Erro ao enviar email de recuperação');
    const result = await response.json();
    if (!result.success) throw new Error(result.message || 'Erro ao enviar email');
    return result;
  };

  const changePassword = async (currentPassword: string, newPassword: string) => {
    const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';
    const token = localStorage.getItem('admin_token');
    if (!token) throw new Error('Usuário não autenticado');
    
    const response = await fetch(`${API_BASE_URL}/auth/change-password`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({ current_password: currentPassword, new_password: newPassword })
    });

    if (!response.ok) throw new Error('Erro ao alterar senha');
    const result = await response.json();
    if (!result.success) throw new Error(result.message || 'Erro ao alterar senha');
    return result;
  };

  const updateProfile = async (profileData: { nome: string; email: string }) => {
    const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';
    const token = localStorage.getItem('admin_token');
    if (!token) throw new Error('Usuário não autenticado');
    
    const response = await fetch(`${API_BASE_URL}/auth/update-profile`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(profileData)
    });

    if (!response.ok) throw new Error('Erro ao atualizar perfil');
    const result = await response.json();
    if (!result.success) throw new Error(result.message || 'Erro ao atualizar perfil');
    
    if (auth.user) {
      const updatedUser = { ...auth.user, ...profileData };
      localStorage.setItem('admin_user', JSON.stringify(updatedUser));
      dispatch({ type: 'LOGIN_SUCCESS', payload: { user: updatedUser, token: auth.token! } });
    }
    return result;
  };

  const logout = () => {
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin_user');
    dispatch({ type: 'LOGOUT' });
  };

  return (
    <AdminContext.Provider value={{ auth, login, logout, resetPassword, changePassword, updateProfile }}>
      {children}
    </AdminContext.Provider>
  );
}

export function useAdmin() {
  const context = useContext(AdminContext);
  if (context === undefined) {
    throw new Error('useAdmin must be used within an AdminProvider');
  }
  return context;
}