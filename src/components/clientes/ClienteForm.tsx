import React, { useState, useEffect } from 'react';
import { useApi, useApiMutation } from '../../hooks/useApi';
import { Cliente, Plano } from '../../types';
import { Modal } from '../common/Modal';
import { Button } from '../common/Button';
import { LoadingSpinner } from '../common/LoadingSpinner';
import { AlertCircle, Save, X } from 'lucide-react';

interface ClienteFormProps {
  isOpen: boolean;
  onClose: () => void;
  cliente?: Cliente | null;
  onSuccess: () => void;
}

export function ClienteForm({ isOpen, onClose, cliente, onSuccess }: ClienteFormProps) {
  const { data: planos } = useApi<Plano[]>('/planos');
  const { mutate, loading, error } = useApiMutation<Cliente>();

  const [formData, setFormData] = useState({
    nome_fantasia: '',
    razao_social: '',
    email: '',
    telefone: '',
    documento: '',
    plano_id: '',
    status: 'ativo' as const
  });

  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    if (cliente) {
      setFormData({
        nome_fantasia: cliente.nome_fantasia,
        razao_social: cliente.razao_social,
        email: cliente.email,
        telefone: cliente.telefone,
        documento: cliente.documento,
        plano_id: cliente.plano_id.toString(),
        status: cliente.status
      });
    } else {
      setFormData({
        nome_fantasia: '',
        razao_social: '',
        email: '',
        telefone: '',
        documento: '',
        plano_id: '',
        status: 'ativo'
      });
    }
    setErrors({});
  }, [cliente, isOpen]);

  const validateForm = () => {
    const newErrors: Record<string, string> = {};

    if (!formData.nome_fantasia.trim()) {
      newErrors.nome_fantasia = 'Nome fantasia é obrigatório';
    }

    if (!formData.razao_social.trim()) {
      newErrors.razao_social = 'Razão social é obrigatória';
    }

    if (!formData.email.trim()) {
      newErrors.email = 'Email é obrigatório';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Email inválido';
    }

    if (!formData.telefone.trim()) {
      newErrors.telefone = 'Telefone é obrigatório';
    }

    if (!formData.documento.trim()) {
      newErrors.documento = 'Documento é obrigatório';
    }

    if (!formData.plano_id) {
      newErrors.plano_id = 'Plano é obrigatório';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) return;

    const url = cliente ? `/clientes/${cliente.id}` : '/clientes';
    const method = cliente ? 'PUT' : 'POST';

    const result = await mutate(url, {
      method,
      body: JSON.stringify({
        ...formData,
        plano_id: parseInt(formData.plano_id)
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
      title={cliente ? 'Editar Cliente' : 'Novo Cliente'}
      maxWidth="lg"
    >
      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Nome Fantasia *
            </label>
            <input
              type="text"
              value={formData.nome_fantasia}
              onChange={(e) => handleChange('nome_fantasia', e.target.value)}
              className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
                errors.nome_fantasia ? 'border-red-300' : 'border-gray-300'
              }`}
              placeholder="Ex: Empresa ABC"
            />
            {errors.nome_fantasia && (
              <p className="mt-1 text-sm text-red-600">{errors.nome_fantasia}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Razão Social *
            </label>
            <input
              type="text"
              value={formData.razao_social}
              onChange={(e) => handleChange('razao_social', e.target.value)}
              className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
                errors.razao_social ? 'border-red-300' : 'border-gray-300'
              }`}
              placeholder="Ex: Empresa ABC Ltda"
            />
            {errors.razao_social && (
              <p className="mt-1 text-sm text-red-600">{errors.razao_social}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Email *
            </label>
            <input
              type="email"
              value={formData.email}
              onChange={(e) => handleChange('email', e.target.value)}
              className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
                errors.email ? 'border-red-300' : 'border-gray-300'
              }`}
              placeholder="contato@empresa.com"
            />
            {errors.email && (
              <p className="mt-1 text-sm text-red-600">{errors.email}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Telefone *
            </label>
            <input
              type="text"
              value={formData.telefone}
              onChange={(e) => handleChange('telefone', e.target.value)}
              className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
                errors.telefone ? 'border-red-300' : 'border-gray-300'
              }`}
              placeholder="(11) 99999-9999"
            />
            {errors.telefone && (
              <p className="mt-1 text-sm text-red-600">{errors.telefone}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Documento (CNPJ/CPF) *
            </label>
            <input
              type="text"
              value={formData.documento}
              onChange={(e) => handleChange('documento', e.target.value)}
              className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
                errors.documento ? 'border-red-300' : 'border-gray-300'
              }`}
              placeholder="00.000.000/0000-00"
            />
            {errors.documento && (
              <p className="mt-1 text-sm text-red-600">{errors.documento}</p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Plano *
            </label>
            <select
              value={formData.plano_id}
              onChange={(e) => handleChange('plano_id', e.target.value)}
              className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
                errors.plano_id ? 'border-red-300' : 'border-gray-300'
              }`}
            >
              <option value="">Selecione um plano</option>
              {planos?.map(plano => (
                <option key={plano.id} value={plano.id}>
                  {plano.nome} - R$ {plano.valor_mensal.toFixed(2)}
                </option>
              ))}
            </select>
            {errors.plano_id && (
              <p className="mt-1 text-sm text-red-600">{errors.plano_id}</p>
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
              <option value="bloqueado">Bloqueado</option>
            </select>
          </div>
        </div>

        {!cliente && (
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div className="flex items-start">
              <AlertCircle className="w-5 h-5 text-blue-600 mr-2 mt-0.5" />
              <div className="text-sm text-blue-800">
                <p className="font-medium mb-1">Criação Automática:</p>
                <ul className="list-disc list-inside space-y-1">
                  <li>Código único do cliente será gerado automaticamente</li>
                  <li>Banco de dados será criado: <code>nicebeec_cliente_xxx</code></li>
                  <li>Usuário do banco será criado: <code>nicebeec_usr_xxx</code></li>
                  <li>Senha segura será gerada automaticamente</li>
                </ul>
              </div>
            </div>
          </div>
        )}

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
            {cliente ? 'Atualizar' : 'Criar'} Cliente
          </Button>
        </div>
      </form>
    </Modal>
  );
}