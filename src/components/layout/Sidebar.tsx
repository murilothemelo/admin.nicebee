import React from 'react';
import { 
  LayoutDashboard, 
  Users, 
  CreditCard, 
  Receipt, 
  Database, 
  Settings,
  User
} from 'lucide-react';

interface SidebarProps {
  currentPage: string;
  onPageChange: (page: string) => void;
}

const menuItems = [
  { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { id: 'clientes', label: 'Clientes', icon: Users },
  { id: 'planos', label: 'Planos', icon: CreditCard },
  { id: 'faturas', label: 'Faturas', icon: Receipt },
  { id: 'backups', label: 'Backups', icon: Database },
  { id: 'configuracoes', label: 'Configurações', icon: Settings },
  { id: 'perfil', label: 'Meu Perfil', icon: User },
];

export function Sidebar({ currentPage, onPageChange }: SidebarProps) {
  return (
    <div className="w-64 bg-white shadow-lg border-r border-gray-200">
      <div className="p-6 border-b border-gray-200">
        <h1 className="text-xl font-bold text-gray-900">NiceBee Admin</h1>
        <p className="text-sm text-gray-600">Painel Multi-Tenant</p>
      </div>
      
      <nav className="mt-6">
        {menuItems.map((item) => {
          const Icon = item.icon;
          const isActive = currentPage === item.id;
          
          return (
            <button
              key={item.id}
              onClick={() => onPageChange(item.id)}
              className={`w-full flex items-center px-6 py-3 text-left hover:bg-gray-50 transition-colors ${
                isActive 
                  ? 'bg-blue-50 border-r-2 border-blue-500 text-blue-700' 
                  : 'text-gray-700 hover:text-gray-900'
              }`}
            >
              <Icon className={`w-5 h-5 mr-3 ${isActive ? 'text-blue-600' : 'text-gray-400'}`} />
              {item.label}
            </button>
          );
        })}
      </nav>
    </div>
  );
}