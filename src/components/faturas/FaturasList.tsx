import React, { useState } from 'react';
import { useApi, useApiMutation } from '../../hooks/useApi';
import { Fatura } from '../../types';
import { Button } from '../common/Button';
import { LoadingSpinner } from '../common/LoadingSpinner';
import { 
  Receipt, 
  Plus, 
  Search, 
  Filter, 
  Download,
  CheckCircle,
  Clock,
  XCircle,
  AlertTriangle,
  DollarSign,
  Calendar
} from 'lucide-react';

export function FaturasList() {
  const { data: faturas, loading, error, refetch } = useApi<Fatura[]>('/faturas');
  const { mutate, loading: mutating } = useApiMutation<Fatura>();
  
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('todos');
  const [dateFilter, setDateFilter] = useState<string>('todos');

  const filteredFaturas = faturas?.filter(fatura => {
    const matchesSearch = fatura.cliente?.nome_fantasia?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         fatura.referencia.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesStatus = statusFilter === 'todos' || fatura.status === statusFilter;
    
    let matchesDate = true;
    if (dateFilter !== 'todos') {
      const today = new Date();
      const vencimento = new Date(fatura.vencimento);
      
      switch (dateFilter) {
        case 'vencidas':
          matchesDate = vencimento < today && fatura.status !== 'pago';
          break;
        case 'vence_hoje':
          matchesDate = vencimento.toDateString() === today.toDateString();
          break;
        case 'proximas':
          const nextWeek = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
          matchesDate = vencimento >= today && vencimento <= nextWeek;
          break;
      }
    }
    
    return matchesSearch && matchesStatus && matchesDate;
  }) || [];

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'pago':
        return <CheckCircle className="w-4 h-4 text-green-500" />;
      case 'pendente':
        return <Clock className="w-4 h-4 text-yellow-500" />;
      case 'vencido':
        return <AlertTriangle className="w-4 h-4 text-red-500" />;
      case 'cancelado':
        return <XCircle className="w-4 h-4 text-gray-500" />;
      default:
        return null;
    }
  };

  const getStatusBadge = (status: string) => {
    const baseClasses = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium";
    switch (status) {
      case 'pago':
        return `${baseClasses} bg-green-100 text-green-800`;
      case 'pendente':
        return `${baseClasses} bg-yellow-100 text-yellow-800`;
      case 'vencido':
        return `${baseClasses} bg-red-100 text-red-800`;
      case 'cancelado':
        return `${baseClasses} bg-gray-100 text-gray-800`;
      default:
        return baseClasses;
    }
  };

  const handleMarkAsPaid = async (fatura: Fatura) => {
    const result = await mutate(`/faturas/${fatura.id}/marcar-pago`, {
      method: 'PUT',
      body: JSON.stringify({
        status: 'pago',
        data_pagamento: new Date().toISOString(),
        forma_pagamento: 'manual'
      })
    });
    
    if (result) {
      refetch();
    }
  };

  const handleGenerateMonthlyInvoices = async () => {
    const result = await mutate('/faturas/gerar-mensais', {
      method: 'POST'
    });
    
    if (result) {
      refetch();
    }
  };

  const calculateTotals = () => {
    if (!faturas) return { total: 0, pago: 0, pendente: 0, vencido: 0 };
    
    return faturas.reduce((acc, fatura) => {
      acc.total += fatura.valor;
      if (fatura.status === 'pago') acc.pago += fatura.valor;
      else if (fatura.status === 'pendente') acc.pendente += fatura.valor;
      else if (fatura.status === 'vencido') acc.vencido += fatura.valor;
      return acc;
    }, { total: 0, pago: 0, pendente: 0, vencido: 0 });
  };

  const totals = calculateTotals();

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
          <span className="text-red-700">Erro ao carregar faturas: {error}</span>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Faturas</h1>
          <p className="text-gray-600">Controle financeiro e cobrança</p>
        </div>
        <Button 
          icon={Plus}
          onClick={handleGenerateMonthlyInvoices}
          loading={mutating}
        >
          Gerar Faturas Mensais
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <DollarSign className="w-6 h-6 text-blue-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Faturado</p>
              <p className="text-2xl font-bold text-gray-900">R$ {totals.total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <CheckCircle className="w-6 h-6 text-green-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Recebido</p>
              <p className="text-2xl font-bold text-gray-900">R$ {totals.pago.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
              <Clock className="w-6 h-6 text-yellow-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Pendente</p>
              <p className="text-2xl font-bold text-gray-900">R$ {totals.pendente.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
            </div>
          </div>
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
              <AlertTriangle className="w-6 h-6 text-red-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Vencido</p>
              <p className="text-2xl font-bold text-gray-900">R$ {totals.vencido.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
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
                placeholder="Buscar por cliente ou referência..."
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
                <option value="pendente">Pendente</option>
                <option value="pago">Pago</option>
                <option value="vencido">Vencido</option>
                <option value="cancelado">Cancelado</option>
              </select>
            </div>
          </div>
          <div className="lg:w-48">
            <div className="relative">
              <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
              <select
                value={dateFilter}
                onChange={(e) => setDateFilter(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="todos">Todas as Datas</option>
                <option value="vencidas">Vencidas</option>
                <option value="vence_hoje">Vence Hoje</option>
                <option value="proximas">Próximas (7 dias)</option>
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
                  Fatura
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Cliente
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Valor
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Vencimento
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Ações
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredFaturas.map((fatura) => (
                <tr key={fatura.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div className="text-sm font-medium text-gray-900">
                        {fatura.referencia}
                      </div>
                      <div className="text-sm text-gray-500">
                        {new Date(fatura.criado_em).toLocaleDateString('pt-BR')}
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">
                      {fatura.cliente?.nome_fantasia || 'N/A'}
                    </div>
                    <div className="text-sm text-gray-500">
                      {fatura.cliente?.email || 'N/A'}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm font-medium text-gray-900">
                      R$ {fatura.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                    </div>
                    {fatura.forma_pagamento && (
                      <div className="text-sm text-gray-500">
                        {fatura.forma_pagamento}
                      </div>
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">
                      {new Date(fatura.vencimento).toLocaleDateString('pt-BR')}
                    </div>
                    {fatura.data_pagamento && (
                      <div className="text-sm text-green-600">
                        Pago em {new Date(fatura.data_pagamento).toLocaleDateString('pt-BR')}
                      </div>
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={getStatusBadge(fatura.status)}>
                      {getStatusIcon(fatura.status)}
                      <span className="ml-1 capitalize">{fatura.status}</span>
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div className="flex items-center justify-end space-x-2">
                      {fatura.status === 'pendente' && (
                        <Button
                          size="sm"
                          onClick={() => handleMarkAsPaid(fatura)}
                          loading={mutating}
                          className="text-xs"
                        >
                          Marcar como Pago
                        </Button>
                      )}
                      <button
                        className="text-blue-600 hover:text-blue-900 p-1 rounded"
                        title="Download"
                      >
                        <Download className="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        
        {filteredFaturas.length === 0 && (
          <div className="text-center py-12">
            <Receipt className="w-12 h-12 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">Nenhuma fatura encontrada</h3>
            <p className="text-gray-500">
              {searchTerm || statusFilter !== 'todos' || dateFilter !== 'todos'
                ? 'Tente ajustar os filtros de busca'
                : 'Gere as faturas mensais para começar'
              }
            </p>
          </div>
        )}
      </div>
    </div>
  );
}