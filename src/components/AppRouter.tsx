import React from 'react';
import { useAdmin } from '../contexts/AdminContext';
import { Login } from './auth/Login';
import { Dashboard } from './dashboard/Dashboard';
import { Sidebar } from './layout/Sidebar';
import { Header } from './layout/Header';
import { LoadingSpinner } from './common/LoadingSpinner';
import { ClientesList } from './clientes/ClientesList';
import { PlanosList } from './planos/PlanosList';
import { FaturasList } from './faturas/FaturasList';
import { BackupsList } from './backups/BackupsList';
import { ConfiguracoesList } from './configuracoes/ConfiguracoesList';
import { MeuPerfil } from './perfil/MeuPerfil';

export function AppRouter() {
  const { auth } = useAdmin();
  const [currentPage, setCurrentPage] = React.useState('dashboard');

  // Mostrar loading enquanto verifica autenticação
  if (auth.isLoading) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center">
        <div className="text-center">
          <LoadingSpinner size="lg" />
          <p className="mt-4 text-gray-600">Verificando autenticação...</p>
        </div>
      </div>
    );
  }

  if (!auth.isAuthenticated) {
    return <Login />;
  }

  return (
    <div className="flex h-screen bg-slate-50">
      <Sidebar currentPage={currentPage} onPageChange={setCurrentPage} />
      <div className="flex-1 flex flex-col overflow-hidden">
        <Header />
        <main className="flex-1 overflow-x-hidden overflow-y-auto">
          <div className="p-6">
            {currentPage === 'dashboard' && <Dashboard />}
            {currentPage === 'clientes' && <ClientesList />}
            {currentPage === 'planos' && <PlanosList />}
            {currentPage === 'faturas' && <FaturasList />}
            {currentPage === 'backups' && <BackupsList />}
            {currentPage === 'configuracoes' && <ConfiguracoesList />}
            {currentPage === 'perfil' && <MeuPerfil />}
          </div>
        </main>
      </div>
    </div>
  );
}