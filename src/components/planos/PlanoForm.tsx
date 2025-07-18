import React, { useState, useEffect } from 'react';
import { useApiMutation } from '../../hooks/useApi';
import { Plano } from '../../types';
import { Modal } from '../common/Modal';
import { Button } from '../common/Button';
import { AlertCircle, Save, X } from 'lucide-react';

interface PlanoFormProps {
  isOpen: boolean;
  onClose: () => void;
  plano?: Plano | null;
  onSuccess: () => void;
}

export function PlanoForm({ isOpen, onClose, plano, onSuccess }: PlanoFormProps) {
  const { mutate, loading, error } = useApiMutation<Plano>();

  const [formData, setFormData] = useState({
    nome: '',
    limite_mb: '',
    usuarios_max: '',
    valor_mensal: '',
    status: 'ativo' as const
  });

  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    if (plano) {
      setFormData({
        nome: plano.nome,
        limite_mb: plano.limite_mb.toString(),
        usuarios_max: plano.usuarios_max.toString(),
        valor_mensal: plano.valor_mensal.toString(),
        status: plano.status
      });
    } else {
      setFormData({
        nome: '',
        limite_mb: '',
        usuarios_max: '',
        valor_mensal: '',
        status: 'ativo'
      });
    }
    setErrors({});
  }, [plano, isOpen]);

  const validateForm = () => {
    const newErrors: Record<string, string> = {};

    if (!formData.nome.trim()) {
      newErrors.nome = 'Nome do plano é obrigatório';
    }

    if (!formData.limite_mb || parseInt(formData.limite_mb) <= 0) {
      newErrors.limite_mb = 'Limite de MB deve ser maior que zero';
    }

    if (!formData.usuarios_max || parseInt(formData.usuarios_max) <= 0) {
      newErrors.usuarios_max = 'Número de usuários deve ser maior que zero';
    }

    if (!formData.valor_mensal || parseFloat(formData.valor_mensal) <= 0) {
      newErrors.valor_mensal = 'Valor mensal deve ser maior que zero';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) return;

    const url = plano ? `/planos/${plano.id}` : '/planos';
    const method = plano ? 'PUT' : 'POST';

    const result = await mutate(url, {
      method,
      body: JSON.stringify({
        ...formData,
        limite_mb: parseInt(formData.limite_mb),
        usuarios_max: parseInt(formData.usuarios_max),
        valor_mensal: parseFloat(formData.valor_mensal)
      })
    });

    if (result) {
      onSuccess();
      onClose();
    }
  };

  const handleChange = (field: string, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: '' }));
    }
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={plano ? 'Editar Plano' : 'Novo Plano'}
    >
      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Nome do Plano *
          </label>
          <input
            type="text"
            value={formData.nome}
            onChange={(e) => handleChange('nome', e.target.value)}
            className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
              errors.nome ? 'border-red-300' : 'border-gray-300'
            }`}
            placeholder="Ex: Plano Básico"
          />
          {errors.nome && (
            <p className="mt-1 text-sm text-red-600">{errors.nome}</p>
          )}
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Limite de Armazenamento (MB) *
            </label>
            <input
              type="number"
              value={formData.limite_mb}
              onChange={(e) => handleChange('limite_mb', e.target.value)}
              className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
                errors.limite_mb ? 'border-red-300' : 'border-gray-300'
              }`}
              placeholder="1000"
              min="1"
            />
            {errors.limite_mb && (
              <p className="mt-1 text-sm text-red-600">{errors.limite_mb}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Máximo de Usuários *
            </label>
            <input
              type="number"
              value={formData.usuarios_max}
              onChange={(e) => handleChange('usuarios_max', e.target.value)}
              className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
                errors.usuarios_max ? 'border-red-300' : 'border-gray-300'
              }`}
              placeholder="10"
              min="1"
            />
            {errors.usuarios_max && (
              <p className="mt-1 text-sm text-red-600">{errors.usuarios_max}</p>
            )}
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Valor Mensal (R$) *
            </label>
            <input
              type="number"
              step="0.01"
              value={formData.valor_mensal}
              onChange={(e) => handleChange('valor_mensal', e.target.value)}
              className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
                errors.valor_mensal ? 'border-red-300' : 'border-gray-300'
              }`}
              placeholder="99.90"
              min="0.01"
            />
            {errors.valor_mensal && (
              <p className="mt-1 text-sm text-red-600">{errors.valor_mensal}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Status
            </label>
            <select
              value={formData.status}
              onChange={(e) => handleChange('status', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="ativo">Ativo</option>
              <option value="inativo">Inativo</option>
            </select>
          </div>
        </div>

        {error && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-4">
            <div className="flex items-center">
              <AlertCircle className="w-5 h-5 text-red-600 mr-2" />
              <span className="text-red-700 text-sm">{error}</span>
            </div>
          </div>
        )}

        <div className="flex space-x-3 pt-4 border-t border-gray-200">
          <Button
            type="button"
            variant="secondary"
            onClick={onClose}
            className="flex-1"
            icon={X}
          >
            Cancelar
          </Button>
          <Button
            type="submit"
            loading={loading}
            className="flex-1"
            icon={Save}
          >
            {plano ? 'Atualizar' : 'Criar'} Plano
          </Button>
        </div>
      </form>
    </Modal>
  );
}