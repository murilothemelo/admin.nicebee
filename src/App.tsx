import React from 'react';
import { AdminProvider } from './contexts/AdminContext';
import { AppRouter } from './components/AppRouter';
import { SessionTimeout } from './components/auth/SessionTimeout';
import './styles/globals.css';

function App() {
  return (
    <AdminProvider>
      <div className="min-h-screen bg-slate-50">
        <AppRouter />
        <SessionTimeout />
      </div>
    </AdminProvider>
  );
}

export default App;