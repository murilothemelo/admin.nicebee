import React from 'react';
import { StatsCards } from './StatsCards';
import { RecentActivity } from './RecentActivity';
import { ClientsChart } from './ClientsChart';
import { AlertsPanel } from './AlertsPanel';

export function Dashboard() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <div className="text-sm text-gray-500">
          Última atualização: {new Date().toLocaleTimeString('pt-BR')}
        </div>
      </div>

      <StatsCards />

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <ClientsChart />
        <AlertsPanel />
      </div>

      <RecentActivity />
    </div>
  );
}