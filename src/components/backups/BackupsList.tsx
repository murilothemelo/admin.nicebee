import React, { useState } from 'react';
import { useApi, useApiMutation } from '../../hooks/useApi';
import { Backup, Cliente } from '../../types';
import { Modal } from '../common/Modal';
import { Button } from '../common/Button';
import { LoadingSpinner } from '../common/LoadingSpinner';
import { 
  Database, 
  Plus, 
  Search, 
  Filter, 
  Download,
  CheckCircle,
  Clock,
  XCircle,
  AlertTriangle,
  HardDrive,
  Calendar,
  RefreshCw,
  Upload
} from 'lucide-react';

export function BackupsList() {
  const { data: backups, loading, error, refetch } = useApi<Backup[]>('/backups');
  const { data: clientes } = useApi<Cliente[]>('/clientes');
  const { mutate, loading: mutating } = useApiMutation<Backup>();
  
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('todos');
  const [tipoFilter, setTipoFilter] = useState<string>('todos');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showRestoreModal, setShowRestoreModal] = useState(false);
  const [selectedBackup, setSelectedBackup] = useState<Backup | null>(null);
  const [selectedCliente, setSelectedCliente] = useState<string>('');

  const filteredBackups = backups?.filter(backup => {
    const matchesSearch = backup.cliente?.nome_fantasia?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         backup.arquivo.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesStatus = statusFilter === 'todos' || backup.status === statusFilter;
    const matchesTipo = tipoFilter === 'todos' || backup.tipo === tipoFilter;
    return matchesSearch && matchesStatus && matchesTipo;
  }) || [];

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'concluido':
        return <CheckCircle className="w-4 h-4 text-green-500" />;
      case 'processando':
        return <Clock className="w-4 h-4 text-yellow-500" />;
      case 'erro':
        return <XCircle className="w-4 h-4 text-red-500" />;
      default:
        return null;
    }
  };

  const getStatusBadge = (status: string) => {
    const baseClasses = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium";
    switch (status) {
      case 'concluido':
        return `${baseClasses} bg-green-100 text-green-800`;
      case 'processando':
        return `${baseClasses} bg-yellow-100 text-yellow-800`;
      case 'erro':
        return `${baseClasses} bg-red-100 text-red-800`;
      default:
        return baseClasses;
    }
  };

  const getTipoBadge = (tipo: string) => {
    const baseClasses = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium";
    return tipo === 'automatico'
      ? `${baseClasses} bg-blue-100 text-blue-800`
      : `${baseClasses} bg-purple-100 text-purple-800`;
  };

  const handleCreateBackup = async () => {
    if (!selectedCliente) return;
    
    const result = await mutate('/backups/criar', {
      method: 'POST',
      body: JSON.stringify({
        cliente_id: parseInt(selectedCliente),
        tipo: 'manual'
      })
    });
    
    if (result) {
      refetch();
      setShowCreateModal(false);
      setSelectedCliente('');
    }
  };

  const handleRestoreBackup = async () => {
    if (!selectedBackup) return;
    
    const result = await mutate('/backups/restaurar', {
      method: 'POST',
      body: JSON.stringify({
        backup_id: selectedBackup.id
      })
    });
    
    if (result) {
      setShowRestoreModal(false);
      setSelectedBackup(null);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-4">
        <div className="flex items-center">
          <AlertTriangle className="w-5 h-5 text-red-600 mr-2" />
          <span className="text-red-700">Erro ao carregar backups: {error}</span>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Backups</h1>
          <p className="text-gray-600">Gerencie backups dos bancos de dados</p>
        </div>
        <Button
          onClick={() => setShowCreateModal(true)}
          icon={Plus}
        >
          Criar Backup
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <Database className="w-6 h-6 text-blue-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total</p>
              <p className="text-2xl font-bold text-gray-900">{backups?.length || 0}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <CheckCircle className="w-6 h-6 text-green-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Concluídos</p>
              <p className="text-2xl font-bold text-gray-900">
                {backups?.filter(b => b.status === 'concluido').length || 0}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
              <Clock className="w-6 h-6 text-yellow-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Processando</p>
              <p className="text-2xl font-bold text-gray-900">
                {backups?.filter(b => b.status === 'processando').length || 0}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <HardDrive className="w-6 h-6 text-purple-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Tamanho Total</p>
              <p className="text-2xl font-bold text-gray-900">
                {backups?.reduce((acc, b) => acc + (b.tamanho_mb || 0), 0).toFixed(1) || '0'} MB
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div className="flex flex-col lg:flex-row gap-4">
          <div className="flex-1">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input
                type="text"
                placeholder="Buscar por cliente ou arquivo..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
          <div className="lg:w-48">
            <div className="relative">
              <Filter className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="todos">Todos os Status</option>
                <option value="concluido">Concluído</option>
                <option value="processando">Processando</option>
                <option value="erro">Erro</option>
              </select>
            </div>
          </div>
          <div className="lg:w-48">
            <div className="relative">
              <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
              <select
                value={tipoFilter}
                onChange={(e) => setTipoFilter(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="todos">Todos os Tipos</option>
                <option value="manual">Manual</option>
                <option value="automatico">Automático</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Cliente
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Arquivo
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Tamanho
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Tipo
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Criado em
                </th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Ações
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredBackups.map((backup) => (
                <tr key={backup.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm font-medium text-gray-900">
                      {backup.cliente?.nome_fantasia || 'N/A'}
                    </div>
                    <div className="text-sm text-gray-500">
                      {backup.cliente?.codigo_cliente || 'N/A'}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900 font-mono">
                      {backup.arquivo}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">
                      {backup.tamanho_mb ? `${backup.tamanho_mb} MB` : '-'}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={getTipoBadge(backup.tipo)}>
                      {backup.tipo === 'automatico' ? 'Automático' : 'Manual'}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={getStatusBadge(backup.status)}>
                      {getStatusIcon(backup.status)}
                      <span className="ml-1 capitalize">{backup.status}</span>
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {new Date(backup.criado_em).toLocaleDateString('pt-BR')}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div className="flex items-center justify-end space-x-2">
                      {backup.status === 'concluido' && (
                        <>
                          <button
                            className="text-blue-600 hover:text-blue-900 p-1 rounded"
                            title="Download"
                          >
                            <Download className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => {
                              setSelectedBackup(backup);
                              setShowRestoreModal(true);
                            }}
                            className="text-green-600 hover:text-green-900 p-1 rounded"
                            title="Restaurar"
                          >
                            <Upload className="w-4 h-4" />
                          </button>
                        </>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Create Backup Modal */}
      <Modal
        isOpen={showCreateModal}
        onClose={() => setShowCreateModal(false)}
        title="Criar Backup"
      >
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Selecione o Cliente
            </label>
            <select
              value={selectedCliente}
              onChange={(e) => setSelectedCliente(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Selecione um cliente</option>
              {clientes?.map(cliente => (
                <option key={cliente.id} value={cliente.id}>
                  {cliente.nome_fantasia} ({cliente.codigo_cliente})
                </option>
              ))}
            </select>
          </div>

          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div className="flex items-start">
              <AlertTriangle className="w-5 h-5 text-blue-600 mr-2 mt-0.5" />
              <div className="text-sm text-blue-800">
                <p className="font-medium mb-1">Informações do Backup:</p>
                <ul className="list-disc list-inside space-y-1">
                  <li>O backup será criado usando mysqldump</li>
                  <li>O processo pode levar alguns minutos</li>
                  <li>O arquivo será salvo no servidor</li>
                  <li>Você pode acompanhar o progresso na lista</li>
                </ul>
              </div>
            </div>
          </div>

          <div className="flex space-x-3 pt-4">
            <Button
              variant="secondary"
              onClick={() => setShowCreateModal(false)}
              className="flex-1"
            >
              Cancelar
            </Button>
            <Button
              onClick={handleCreateBackup}
              loading={mutating}
              disabled={!selectedCliente}
              className="flex-1"
              icon={RefreshCw}
            >
              Criar Backup
            </Button>
          </div>
        </div>
      </Modal>

      {/* Restore Backup Modal */}
      <Modal
        isOpen={showRestoreModal}
        onClose={() => setShowRestoreModal(false)}
        title="Restaurar Backup"
      >
        <div className="space-y-4">
          <div className="flex items-center space-x-3">
            <div className="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
              <AlertTriangle className="w-6 h-6 text-orange-600" />
            </div>
            <div>
              <h3 className="text-lg font-medium text-gray-900">
                Confirmar Restauração
              </h3>
              <p className="text-sm text-gray-600">
                Esta ação irá sobrescrever os dados atuais.
              </p>
            </div>
          </div>
          
          <div className="bg-red-50 border border-red-200 rounded-lg p-4">
            <p className="text-sm text-red-800">
              <strong>ATENÇÃO:</strong> A restauração irá substituir todos os dados 
              atuais do banco <code className="bg-red-100 px-1 rounded">
                {selectedBackup?.cliente?.banco_nome}
              </code> pelos dados do backup selecionado.
            </p>
          </div>

          <div className="bg-gray-50 rounded-lg p-4">
            <h4 className="font-medium text-gray-900 mb-2">Detalhes do Backup:</h4>
            <div className="space-y-1 text-sm text-gray-600">
              <p><strong>Cliente:</strong> {selectedBackup?.cliente?.nome_fantasia}</p>
              <p><strong>Arquivo:</strong> {selectedBackup?.arquivo}</p>
              <p><strong>Tamanho:</strong> {selectedBackup?.tamanho_mb} MB</p>
              <p><strong>Criado em:</strong> {selectedBackup && new Date(selectedBackup.criado_em).toLocaleString('pt-BR')}</p>
            </div>
          </div>

          <div className="flex space-x-3 pt-4">
            <Button
              variant="secondary"
              onClick={() => setShowRestoreModal(false)}
              className="flex-1"
            >
              Cancelar
            </Button>
            <Button
              variant="danger"
              onClick={handleRestoreBackup}
              loading={mutating}
              className="flex-1"
              icon={Upload}
            >
              Restaurar Backup
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}