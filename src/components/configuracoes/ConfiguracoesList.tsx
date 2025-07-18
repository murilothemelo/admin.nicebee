import React, { useState } from 'react';
import { Button } from '../common/Button';
import { 
  Settings, 
  Database, 
  Mail, 
  Shield, 
  Clock, 
  HardDrive,
  Save,
  RefreshCw,
  AlertTriangle,
  CheckCircle,
  Server,
  Key
} from 'lucide-react';

interface ConfigSection {
  id: string;
  title: string;
  description: string;
  icon: React.ComponentType<{ className?: string }>;
}

const configSections: ConfigSection[] = [
  {
    id: 'sistema',
    title: 'Configurações do Sistema',
    description: 'Configurações gerais da aplicação',
    icon: Settings
  },
  {
    id: 'banco',
    title: 'Banco de Dados',
    description: 'Configurações de conexão e backup',
    icon: Database
  },
  {
    id: 'email',
    title: 'Configurações de Email',
    description: 'SMTP e notificações automáticas',
    icon: Mail
  },
  {
    id: 'seguranca',
    title: 'Segurança',
    description: 'Autenticação e controle de acesso',
    icon: Shield
  }
];

export function ConfiguracoesList() {
  const [activeSection, setActiveSection] = useState('sistema');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  const [configs, setConfigs] = useState({
    sistema: {
      app_name: 'NiceBee Admin',
      app_version: '1.0.0',
      timezone: 'America/Sao_Paulo',
      debug_mode: false,
      maintenance_mode: false
    },
    banco: {
      host: 'localhost',
      port: '3306',
      backup_retention_days: '30',
      auto_backup_enabled: true,
      auto_backup_time: '02:00'
    },
    email: {
      smtp_host: 'mail.nicebee.com.br',
      smtp_port: '587',
      smtp_user: 'noreply@nicebee.com.br',
      smtp_password: '',
      from_name: 'NiceBee Admin',
      notifications_enabled: true
    },
    seguranca: {
      session_timeout: '30',
      max_login_attempts: '5',
      password_min_length: '8',
      require_2fa: false,
      jwt_expiration: '8'
    }
  });

  const handleConfigChange = (section: string, key: string, value: any) => {
    setConfigs(prev => ({
      ...prev,
      [section]: {
        ...prev[section as keyof typeof prev],
        [key]: value
      }
    }));
  };

  const handleSave = async () => {
    setLoading(true);
    setMessage(null);
    
    try {
      // Simular salvamento
      await new Promise(resolve => setTimeout(resolve, 1000));
      setMessage({ type: 'success', text: 'Configurações salvas com sucesso!' });
    } catch (error) {
      setMessage({ type: 'error', text: 'Erro ao salvar configurações' });
    } finally {
      setLoading(false);
    }
  };

  const renderSistemaConfig = () => (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Nome da Aplicação
          </label>
          <input
            type="text"
            value={configs.sistema.app_name}
            onChange={(e) => handleConfigChange('sistema', 'app_name', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Versão
          </label>
          <input
            type="text"
            value={configs.sistema.app_version}
            onChange={(e) => handleConfigChange('sistema', 'app_version', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Fuso Horário
          </label>
          <select
            value={configs.sistema.timezone}
            onChange={(e) => handleConfigChange('sistema', 'timezone', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="America/Sao_Paulo">São Paulo (GMT-3)</option>
            <option value="America/New_York">New York (GMT-5)</option>
            <option value="Europe/London">London (GMT+0)</option>
          </select>
        </div>
      </div>
      
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h4 className="text-sm font-medium text-gray-900">Modo Debug</h4>
            <p className="text-sm text-gray-500">Ativar logs detalhados para desenvolvimento</p>
          </div>
          <label className="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              checked={configs.sistema.debug_mode}
              onChange={(e) => handleConfigChange('sistema', 'debug_mode', e.target.checked)}
              className="sr-only peer"
            />
            <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
          </label>
        </div>
        
        <div className="flex items-center justify-between">
          <div>
            <h4 className="text-sm font-medium text-gray-900">Modo Manutenção</h4>
            <p className="text-sm text-gray-500">Bloquear acesso ao sistema para manutenção</p>
          </div>
          <label className="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              checked={configs.sistema.maintenance_mode}
              onChange={(e) => handleConfigChange('sistema', 'maintenance_mode', e.target.checked)}
              className="sr-only peer"
            />
            <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
          </label>
        </div>
      </div>
    </div>
  );

  const renderBancoConfig = () => (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Host do Banco
          </label>
          <input
            type="text"
            value={configs.banco.host}
            onChange={(e) => handleConfigChange('banco', 'host', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Porta
          </label>
          <input
            type="text"
            value={configs.banco.port}
            onChange={(e) => handleConfigChange('banco', 'port', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Retenção de Backups (dias)
          </label>
          <input
            type="number"
            value={configs.banco.backup_retention_days}
            onChange={(e) => handleConfigChange('banco', 'backup_retention_days', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Horário do Backup Automático
          </label>
          <input
            type="time"
            value={configs.banco.auto_backup_time}
            onChange={(e) => handleConfigChange('banco', 'auto_backup_time', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
      </div>
      
      <div className="flex items-center justify-between">
        <div>
          <h4 className="text-sm font-medium text-gray-900">Backup Automático</h4>
          <p className="text-sm text-gray-500">Executar backup diário automaticamente</p>
        </div>
        <label className="relative inline-flex items-center cursor-pointer">
          <input
            type="checkbox"
            checked={configs.banco.auto_backup_enabled}
            onChange={(e) => handleConfigChange('banco', 'auto_backup_enabled', e.target.checked)}
            className="sr-only peer"
          />
          <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
        </label>
      </div>
    </div>
  );

  const renderEmailConfig = () => (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Servidor SMTP
          </label>
          <input
            type="text"
            value={configs.email.smtp_host}
            onChange={(e) => handleConfigChange('email', 'smtp_host', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Porta SMTP
          </label>
          <input
            type="text"
            value={configs.email.smtp_port}
            onChange={(e) => handleConfigChange('email', 'smtp_port', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Usuário SMTP
          </label>
          <input
            type="email"
            value={configs.email.smtp_user}
            onChange={(e) => handleConfigChange('email', 'smtp_user', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Senha SMTP
          </label>
          <input
            type="password"
            value={configs.email.smtp_password}
            onChange={(e) => handleConfigChange('email', 'smtp_password', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="••••••••"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Nome do Remetente
          </label>
          <input
            type="text"
            value={configs.email.from_name}
            onChange={(e) => handleConfigChange('email', 'from_name', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
      </div>
      
      <div className="flex items-center justify-between">
        <div>
          <h4 className="text-sm font-medium text-gray-900">Notificações por Email</h4>
          <p className="text-sm text-gray-500">Enviar notificações automáticas por email</p>
        </div>
        <label className="relative inline-flex items-center cursor-pointer">
          <input
            type="checkbox"
            checked={configs.email.notifications_enabled}
            onChange={(e) => handleConfigChange('email', 'notifications_enabled', e.target.checked)}
            className="sr-only peer"
          />
          <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
        </label>
      </div>
    </div>
  );

  const renderSegurancaConfig = () => (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Timeout de Sessão (minutos)
          </label>
          <input
            type="number"
            value={configs.seguranca.session_timeout}
            onChange={(e) => handleConfigChange('seguranca', 'session_timeout', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Máximo de Tentativas de Login
          </label>
          <input
            type="number"
            value={configs.seguranca.max_login_attempts}
            onChange={(e) => handleConfigChange('seguranca', 'max_login_attempts', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Tamanho Mínimo da Senha
          </label>
          <input
            type="number"
            value={configs.seguranca.password_min_length}
            onChange={(e) => handleConfigChange('seguranca', 'password_min_length', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Expiração JWT (horas)
          </label>
          <input
            type="number"
            value={configs.seguranca.jwt_expiration}
            onChange={(e) => handleConfigChange('seguranca', 'jwt_expiration', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
      </div>
      
      <div className="flex items-center justify-between">
        <div>
          <h4 className="text-sm font-medium text-gray-900">Autenticação de Dois Fatores</h4>
          <p className="text-sm text-gray-500">Exigir 2FA para todos os usuários</p>
        </div>
        <label className="relative inline-flex items-center cursor-pointer">
          <input
            type="checkbox"
            checked={configs.seguranca.require_2fa}
            onChange={(e) => handleConfigChange('seguranca', 'require_2fa', e.target.checked)}
            className="sr-only peer"
          />
          <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
        </label>
      </div>
    </div>
  );

  const renderContent = () => {
    switch (activeSection) {
      case 'sistema':
        return renderSistemaConfig();
      case 'banco':
        return renderBancoConfig();
      case 'email':
        return renderEmailConfig();
      case 'seguranca':
        return renderSegurancaConfig();
      default:
        return renderSistemaConfig();
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Configurações</h1>
          <p className="text-gray-600">Gerencie as configurações do sistema</p>
        </div>
        <Button
          onClick={handleSave}
          loading={loading}
          icon={Save}
        >
          Salvar Configurações
        </Button>
      </div>

      {/* Message */}
      {message && (
        <div className={`rounded-lg p-4 ${
          message.type === 'success' 
            ? 'bg-green-50 border border-green-200' 
            : 'bg-red-50 border border-red-200'
        }`}>
          <div className="flex items-center">
            {message.type === 'success' ? (
              <CheckCircle className="w-5 h-5 text-green-600 mr-2" />
            ) : (
              <AlertTriangle className="w-5 h-5 text-red-600 mr-2" />
            )}
            <span className={`text-sm font-medium ${
              message.type === 'success' ? 'text-green-800' : 'text-red-800'
            }`}>
              {message.text}
            </span>
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {/* Sidebar */}
        <div className="lg:col-span-1">
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <nav className="space-y-2">
              {configSections.map((section) => {
                const Icon = section.icon;
                return (
                  <button
                    key={section.id}
                    onClick={() => setActiveSection(section.id)}
                    className={`w-full flex items-center px-3 py-2 text-left rounded-lg transition-colors ${
                      activeSection === section.id
                        ? 'bg-blue-50 text-blue-700 border border-blue-200'
                        : 'text-gray-700 hover:bg-gray-50'
                    }`}
                  >
                    <Icon className={`w-5 h-5 mr-3 ${
                      activeSection === section.id ? 'text-blue-600' : 'text-gray-400'
                    }`} />
                    <div>
                      <div className="font-medium">{section.title}</div>
                      <div className="text-xs text-gray-500">{section.description}</div>
                    </div>
                  </button>
                );
              })}
            </nav>
          </div>
        </div>

        {/* Content */}
        <div className="lg:col-span-3">
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div className="mb-6">
              <h2 className="text-lg font-semibold text-gray-900">
                {configSections.find(s => s.id === activeSection)?.title}
              </h2>
              <p className="text-sm text-gray-600">
                {configSections.find(s => s.id === activeSection)?.description}
              </p>
            </div>
            
            {renderContent()}
          </div>
        </div>
      </div>
    </div>
  );
}