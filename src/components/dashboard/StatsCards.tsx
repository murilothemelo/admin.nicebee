import React from 'react';
import { Users, DollarSign, HardDrive, AlertTriangle } from 'lucide-react';
import { useApi } from '../../hooks/useApi';
import { DashboardStats } from '../../types';
import { LoadingSpinner } from '../common/LoadingSpinner';

export function StatsCards() {
  const { data: stats, loading, error } = useApi<DashboardStats>('/dashboard/stats');

  if (loading) {
    return (
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
            <div className="flex items-center justify-center h-20">
              <LoadingSpinner size="md" />
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (error || !stats) {
    return (
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-red-50 border border-red-200 rounded-xl p-6 col-span-full">
          <div className="flex items-center">
            <AlertTriangle className="w-5 h-5 text-red-600 mr-2" />
            <span className="text-red-700">Erro ao carregar estatísticas</span>
          </div>
        </div>
      </div>
    );
  }

  const statsData = [
    {
      title: 'Clientes Ativos',
      value: stats.clientes_ativos.toString(),
      change: '+12%',
      changeType: 'positive' as const,
      icon: Users,
      color: 'bg-blue-500'
    },
    {
      title: 'Receita Mensal',
      value: `R$ ${stats.receita_mensal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`,
      change: '+8%',
      changeType: 'positive' as const,
      icon: DollarSign,
      color: 'bg-green-500'
    },
    {
      title: 'Uso de Disco',
      value: `${(stats.uso_total_mb / 1024).toFixed(1)} GB`,
      change: '+15%',
      changeType: 'neutral' as const,
      icon: HardDrive,
      color: 'bg-purple-500'
    },
    {
      title: 'Alertas',
      value: stats.faturas_pendentes.toString(),
      change: '-2',
      changeType: 'positive' as const,
      icon: AlertTriangle,
      color: 'bg-orange-500'
    }
  ];

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      {statsData.map((stat) => {
        const Icon = stat.icon;
        return (
          <div key={stat.title} className="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{stat.title}</p>
                <p className="text-2xl font-bold text-gray-900 mt-1">{stat.value}</p>
              </div>
              <div className={`w-12 h-12 ${stat.color} rounded-lg flex items-center justify-center`}>
                <Icon className="w-6 h-6 text-white" />
              </div>
            </div>
            <div className="mt-4 flex items-center">
              <span className={`text-sm font-medium ${
                stat.changeType === 'positive' 
                  ? 'text-green-600' 
                  : stat.changeType === 'negative' 
                  ? 'text-red-600' 
                  : 'text-gray-600'
              }`}>
                {stat.change}
              </span>
              <span className="text-sm text-gray-500 ml-1">vs mês anterior</span>
            </div>
          </div>
        );
      })}
    </div>
  );
}