import React, { useState } from 'react';
import { useApi, useApiMutation } from '../../hooks/useApi';
import { Plano } from '../../types';
import { Modal } from '../common/Modal';
import { Button } from '../common/Button';
import { LoadingSpinner } from '../common/LoadingSpinner';
import { 
  CreditCard, 
  Plus, 
  Edit, 
  Trash2, 
  AlertCircle,
  CheckCircle,
  XCircle,
  HardDrive,
  Users,
  DollarSign
} from 'lucide-react';
import { PlanoForm } from './PlanoForm';

export function PlanosList() {
  const { data: planos, loading, error, refetch } = useApi<Plano[]>('/planos');
  const { mutate, loading: mutating } = useApiMutation<Plano>();
  
  const [showModal, setShowModal] = useState(false);
  const [editingPlano, setEditingPlano] = useState<Plano | null>(null);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [planoToDelete, setPlanoToDelete] = useState<Plano | null>(null);

  const handleEdit = (plano: Plano) => {
    setEditingPlano(plano);
    setShowModal(true);
  };

  const handleDelete = async () => {
    if (!planoToDelete) return;
    
    const result = await mutate(`/planos/${planoToDelete.id}`, {
      method: 'DELETE'
    });
    
    if (result) {
      refetch();
      setShowDeleteModal(false);
      setPlanoToDelete(null);
    }
  };

  const getStatusIcon = (status: string) => {
    return status === 'ativo' 
      ? <CheckCircle className="w-4 h-4 text-green-500" />
      : <XCircle className="w-4 h-4 text-gray-500" />;
  };

  const getStatusBadge = (status: string) => {
    const baseClasses = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium";
    return status === 'ativo'
      ? `${baseClasses} bg-green-100 text-green-800`
      : `${baseClasses} bg-gray-100 text-gray-800`;
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
          <AlertCircle className="w-5 h-5 text-red-600 mr-2" />
          <span className="text-red-700">Erro ao carregar planos: {error}</span>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Planos</h1>
          <p className="text-gray-600">Gerencie os planos de assinatura</p>
        </div>
        <Button
          onClick={() => {
            setEditingPlano(null);
            setShowModal(true);
          }}
          icon={Plus}
        >
          Novo Plano
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <CreditCard className="w-6 h-6 text-blue-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total de Planos</p>
              <p className="text-2xl font-bold text-gray-900">{planos?.length || 0}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <CheckCircle className="w-6 h-6 text-green-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Planos Ativos</p>
              <p className="text-2xl font-bold text-gray-900">
                {planos?.filter(p => p.status === 'ativo').length || 0}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <DollarSign className="w-6 h-6 text-purple-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Valor Médio</p>
              <p className="text-2xl font-bold text-gray-900">
                R$ {planos?.length ? 
                  (planos.reduce((acc, p) => acc + p.valor_mensal, 0) / planos.length).toFixed(2) 
                  : '0,00'
                }
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Plans Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {planos?.map((plano) => (
          <div key={plano.id} className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            <div className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold text-gray-900">{plano.nome}</h3>
                <span className={getStatusBadge(plano.status)}>
                  {getStatusIcon(plano.status)}
                  <span className="ml-1 capitalize">{plano.status}</span>
                </span>
              </div>
              
              <div className="space-y-4">
                <div className="text-center">
                  <div className="text-3xl font-bold text-gray-900">
                    R$ {plano.valor_mensal.toFixed(2)}
                  </div>
                  <div className="text-sm text-gray-500">por mês</div>
                </div>
                
                <div className="space-y-3">
                  <div className="flex items-center text-sm text-gray-600">
                    <HardDrive className="w-4 h-4 mr-2 text-gray-400" />
                    <span>{plano.limite_mb} MB de armazenamento</span>
                  </div>
                  <div className="flex items-center text-sm text-gray-600">
                    <Users className="w-4 h-4 mr-2 text-gray-400" />
                    <span>Até {plano.usuarios_max} usuários</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="px-6 py-4 bg-gray-50 border-t border-gray-200">
              <div className="flex items-center justify-between">
                <div className="text-xs text-gray-500">
                  Criado em {new Date(plano.criado_em).toLocaleDateString('pt-BR')}
                </div>
                <div className="flex items-center space-x-2">
                  <button
                    onClick={() => handleEdit(plano)}
                    className="text-blue-600 hover:text-blue-900 p-1 rounded"
                    title="Editar"
                  >
                    <Edit className="w-4 h-4" />
                  </button>
                  <button
                    onClick={() => {
                      setPlanoToDelete(plano);
                      setShowDeleteModal(true);
                    }}
                    className="text-red-600 hover:text-red-900 p-1 rounded"
                    title="Excluir"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Form Modal */}
      <PlanoForm
        isOpen={showModal}
        onClose={() => setShowModal(false)}
        plano={editingPlano}
        onSuccess={refetch}
      />

      {/* Delete Modal */}
      <Modal
        isOpen={showDeleteModal}
        onClose={() => {
          setShowDeleteModal(false);
          setPlanoToDelete(null);
        }}
        title="Confirmar Exclusão"
      >
        <div className="space-y-4">
          <div className="flex items-center space-x-3">
            <div className="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
              <AlertCircle className="w-6 h-6 text-red-600" />
            </div>
            <div>
              <h3 className="text-lg font-medium text-gray-900">
                Excluir Plano
              </h3>
              <p className="text-sm text-gray-600">
                Esta ação não pode ser desfeita.
              </p>
            </div>
          </div>
          
          <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p className="text-sm text-yellow-800">
              <strong>Atenção:</strong> Certifique-se de que nenhum cliente está 
              utilizando este plano antes de excluí-lo.
            </p>
          </div>

          <div className="flex space-x-3 pt-4">
            <Button
              variant="secondary"
              onClick={() => setShowDeleteModal(false)}
              className="flex-1"
            >
              Cancelar
            </Button>
            <Button
              variant="danger"
              onClick={handleDelete}
              loading={mutating}
              className="flex-1"
            >
              Excluir Plano
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}