import React from 'react';
import { Activity, User, CreditCard, Database, Settings } from 'lucide-react';

interface ActivityItem {
  id: string;
  type: 'user' | 'payment' | 'backup' | 'system';
  title: string;
  description: string;
  time: string;
  user: string;
  icon: React.ComponentType<{ className?: string }>;
}

const activities: ActivityItem[] = [
  {
    id: '1',
    type: 'user',
    title: 'Novo cliente cadastrado',
    description: 'TechSolutions Ltda foi adicionada ao sistema',
    time: '10 min atrás',
    user: 'Admin',
    icon: User
  },
  {
    id: '2',
    type: 'payment',
    title: 'Pagamento recebido',
    description: 'Fatura de R$ 199,90 - Cliente ABC Corp',
    time: '25 min atrás',
    user: 'Sistema',
    icon: CreditCard
  },
  {
    id: '3',
    type: 'backup',
    title: 'Backup realizado',
    description: 'Backup automático de 23 bancos de dados',
    time: '1 hora atrás',
    user: 'Sistema',
    icon: Database
  },
  {
    id: '4',
    type: 'system',
    title: 'Plano atualizado',
    description: 'Cliente XYZ Corp migrou para plano Premium',
    time: '2 horas atrás',
    user: 'Admin',
    icon: Settings
  },
  {
    id: '5',
    type: 'user',
    title: 'Cliente suspenso',
    description: 'DEF Ltda foi suspenso por inadimplência',
    time: '3 horas atrás',
    user: 'Admin',
    icon: User
  }
];

export function RecentActivity() {
  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-200">
      <div className="p-6 border-b border-gray-200">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-semibold text-gray-900">Atividade Recente</h3>
          <Activity className="w-5 h-5 text-gray-500" />
        </div>
      </div>
      
      <div className="p-6">
        <div className="space-y-4">
          {activities.map((activity) => {
            const Icon = activity.icon;
            return (
              <div key={activity.id} className="flex items-start space-x-4">
                <div className={`w-10 h-10 rounded-full flex items-center justify-center ${
                  activity.type === 'user' 
                    ? 'bg-blue-100 text-blue-600' 
                    : activity.type === 'payment' 
                    ? 'bg-green-100 text-green-600' 
                    : activity.type === 'backup' 
                    ? 'bg-purple-100 text-purple-600' 
                    : 'bg-gray-100 text-gray-600'
                }`}>
                  <Icon className="w-5 h-5" />
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center justify-between">
                    <p className="text-sm font-medium text-gray-900">{activity.title}</p>
                    <span className="text-xs text-gray-500">{activity.time}</span>
                  </div>
                  <p className="text-sm text-gray-600 mt-1">{activity.description}</p>
                  <p className="text-xs text-gray-500 mt-1">por {activity.user}</p>
                </div>
              </div>
            );
          })}
        </div>
        
        <div className="mt-6 pt-4 border-t border-gray-200">
          <button className="w-full text-center text-sm text-blue-600 hover:text-blue-700 font-medium">
            Ver histórico completo
          </button>
        </div>
      </div>
    </div>
  );
}