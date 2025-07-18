import React from 'react';
import { AlertTriangle, HardDrive, CreditCard, Database, Clock } from 'lucide-react';
import { useApi } from '../../hooks/useApi';
import { Cliente, Fatura } from '../../types';

interface Alert {
  id: string;
  type: 'warning' | 'error' | 'info';
  title: string;
  message: string;
  time: string;
  icon: React.ComponentType<{ className?: string }>;
}

export function AlertsPanel() {
  const { data: clientes } = useApi<Cliente[]>('/clientes');
  const { data: faturas } = useApi<Fatura[]>('/faturas');

  const generateAlerts = (): Alert[] => {
    const alerts: Alert[] = [];

    // Alertas de limite de disco
    if (clientes) {
      clientes.forEach(cliente => {
        if (cliente.plano && cliente.uso_mb / cliente.plano.limite_mb > 0.9) {
          alerts.push({
            id: `disk_${cliente.id}`,
            type: 'warning',
            title: 'Limite de Disco',
            message: `${cliente.nome_fantasia} próximo do limite (${Math.round((cliente.uso_mb / cliente.plano.limite_mb) * 100)}% usado)`,
            time: '2 min atrás',
            icon: HardDrive
          });
        }
      });
    }

    // Alertas de faturas vencidas
    if (faturas) {
      const faturasVencidas = faturas.filter(f => {
        const vencimento = new Date(f.vencimento);
        const hoje = new Date();
        return f.status === 'vencido' || (f.status === 'pendente' && vencimento < hoje);
      });

      faturasVencidas.slice(0, 3).forEach(fatura => {
        const diasVencido = Math.floor((Date.now() - new Date(fatura.vencimento).getTime()) / (1000 * 60 * 60 * 24));
        alerts.push({
          id: `invoice_${fatura.id}`,
          type: 'error',
          title: 'Fatura Vencida',
          message: `${fatura.cliente?.nome_fantasia || 'Cliente'} - R$ ${fatura.valor.toFixed(2)} vencida há ${diasVencido} dias`,
          time: `${diasVencido} dias atrás`,
          icon: CreditCard
        });
      });
    }

    // Alerta de backup (simulado)
    alerts.push({
      id: 'backup_success',
      type: 'info',
      title: 'Backup Concluído',
      message: 'Backup automático de 15 clientes finalizado com sucesso',
      time: '3 horas atrás',
      icon: Database
    });

    return alerts.slice(0, 5); // Limitar a 5 alertas
  };

  const alerts = generateAlerts();

  return (
    <div className="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
      <div className="flex items-center justify-between mb-6">
        <h3 className="text-lg font-semibold text-gray-900">Alertas Recentes</h3>
        <div className="flex items-center">
          <AlertTriangle className="w-5 h-5 text-orange-500 mr-1" />
          <span className="text-sm font-medium text-orange-600">{alerts.filter(a => a.type === 'warning' || a.type === 'error').length}</span>
        </div>
      </div>
      
      <div className="space-y-4">
        {alerts.length > 0 ? alerts.map((alert) => {
          const Icon = alert.icon;
          return (
            <div key={alert.id} className="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
              <div className={`w-8 h-8 rounded-full flex items-center justify-center ${
                alert.type === 'warning' 
                  ? 'bg-orange-100 text-orange-600' 
                  : alert.type === 'error' 
                  ? 'bg-red-100 text-red-600' 
                  : 'bg-blue-100 text-blue-600'
              }`}>
                <Icon className="w-4 h-4" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900">{alert.title}</p>
                <p className="text-sm text-gray-600 mt-1">{alert.message}</p>
                <p className="text-xs text-gray-500 mt-1 flex items-center">
                  <Clock className="w-3 h-3 mr-1" />
                  {alert.time}
                </p>
              </div>
            </div>
          );
        }) : (
          <div className="text-center py-8">
            <AlertTriangle className="w-12 h-12 text-gray-300 mx-auto mb-4" />
            <p className="text-gray-500">Nenhum alerta no momento</p>
          </div>
        )}
      </div>
      
      {alerts.length > 0 && (
        <div className="mt-6 pt-4 border-t border-gray-200">
          <button className="w-full text-center text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors">
            Ver todos os alertas ({alerts.length})
          </button>
        </div>
      )}
    </div>
  );
}