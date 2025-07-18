export interface User {
  id: number;
  nome: string;
  email: string;
  tipo: 'admin' | 'operador';
  ultimo_login?: string;
  status: 'ativo' | 'inativo';
  criado_em: string;
}

export interface Cliente {
  id: number;
  codigo_cliente: string;
  nome_fantasia: string;
  razao_social: string;
  email: string;
  telefone: string;
  documento: string;
  plano_id: number;
  status: 'ativo' | 'inativo' | 'bloqueado';
  banco_nome: string;
  banco_usuario: string;
  banco_senha: string;
  uso_mb: number;
  criado_em: string;
  plano?: Plano;
}

export interface Plano {
  id: number;
  nome: string;
  limite_mb: number;
  usuarios_max: number;
  valor_mensal: number;
  status: 'ativo' | 'inativo';
  criado_em: string;
}

export interface Fatura {
  id: number;
  cliente_id: number;
  referencia: string;
  vencimento: string;
  valor: number;
  status: 'pendente' | 'pago' | 'vencido' | 'cancelado';
  forma_pagamento?: string;
  data_pagamento?: string;
  criado_em: string;
  cliente?: Cliente;
}

export interface Backup {
  id: number;
  cliente_id: number;
  arquivo: string;
  tamanho_mb: number;
  status: 'processando' | 'concluido' | 'erro';
  tipo: 'manual' | 'automatico';
  criado_em: string;
  cliente?: Cliente;
}

export interface Log {
  id: number;
  usuario_id: number;
  acao: string;
  detalhes: string;
  ip: string;
  criado_em: string;
  usuario?: User;
}

export interface DashboardStats {
  total_clientes: number;
  clientes_ativos: number;
  receita_mensal: number;
  uso_total_mb: number;
  faturas_pendentes: number;
  backups_hoje: number;
}

export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message?: string;
  error?: string;
}

export interface AuthState {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  isAuthenticated: boolean;
}