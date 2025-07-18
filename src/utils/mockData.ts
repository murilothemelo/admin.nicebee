// Mock data para desenvolvimento e testes
import { Cliente, Plano, Fatura, Backup, Log, User, DashboardStats } from '../types';

export const mockPlanos: Plano[] = [
  {
    id: 1,
    nome: 'Básico',
    limite_mb: 500,
    usuarios_max: 5,
    valor_mensal: 99.90,
    status: 'ativo',
    criado_em: '2024-01-15T10:00:00Z'
  },
  {
    id: 2,
    nome: 'Profissional',
    limite_mb: 2000,
    usuarios_max: 15,
    valor_mensal: 199.90,
    status: 'ativo',
    criado_em: '2024-01-15T10:00:00Z'
  },
  {
    id: 3,
    nome: 'Empresarial',
    limite_mb: 5000,
    usuarios_max: 50,
    valor_mensal: 399.90,
    status: 'ativo',
    criado_em: '2024-01-15T10:00:00Z'
  },
  {
    id: 4,
    nome: 'Premium',
    limite_mb: 10000,
    usuarios_max: 100,
    valor_mensal: 699.90,
    status: 'ativo',
    criado_em: '2024-01-15T10:00:00Z'
  }
];

export const mockClientes: Cliente[] = [
  {
    id: 1,
    codigo_cliente: 'abc123',
    nome_fantasia: 'TechSolutions Ltda',
    razao_social: 'TechSolutions Tecnologia Ltda',
    email: 'contato@techsolutions.com',
    telefone: '(11) 99999-1234',
    documento: '12.345.678/0001-90',
    plano_id: 2,
    status: 'ativo',
    banco_nome: 'nicebeec_cliente_abc123',
    banco_usuario: 'nicebeec_usr_abc123',
    banco_senha: 'encrypted_password_hash',
    uso_mb: 1200,
    criado_em: '2024-01-20T14:30:00Z',
    plano: mockPlanos[1]
  },
  {
    id: 2,
    codigo_cliente: 'def456',
    nome_fantasia: 'ABC Corp',
    razao_social: 'ABC Corporação S.A.',
    email: 'admin@abccorp.com.br',
    telefone: '(11) 88888-5678',
    documento: '98.765.432/0001-10',
    plano_id: 3,
    status: 'ativo',
    banco_nome: 'nicebeec_cliente_def456',
    banco_usuario: 'nicebeec_usr_def456',
    banco_senha: 'encrypted_password_hash',
    uso_mb: 3800,
    criado_em: '2024-02-01T09:15:00Z',
    plano: mockPlanos[2]
  },
  {
    id: 3,
    codigo_cliente: 'ghi789',
    nome_fantasia: 'StartupXYZ',
    razao_social: 'StartupXYZ Inovação Ltda',
    email: 'hello@startupxyz.com',
    telefone: '(11) 77777-9012',
    documento: '11.222.333/0001-44',
    plano_id: 1,
    status: 'bloqueado',
    banco_nome: 'nicebeec_cliente_ghi789',
    banco_usuario: 'nicebeec_usr_ghi789',
    banco_senha: 'encrypted_password_hash',
    uso_mb: 480,
    criado_em: '2024-02-10T16:45:00Z',
    plano: mockPlanos[0]
  },
  {
    id: 4,
    codigo_cliente: 'jkl012',
    nome_fantasia: 'Inovação Digital',
    razao_social: 'Inovação Digital Soluções Ltda',
    email: 'contato@inovacaodigital.com',
    telefone: '(11) 66666-3456',
    documento: '22.333.444/0001-55',
    plano_id: 4,
    status: 'ativo',
    banco_nome: 'nicebeec_cliente_jkl012',
    banco_usuario: 'nicebeec_usr_jkl012',
    banco_senha: 'encrypted_password_hash',
    uso_mb: 7200,
    criado_em: '2024-02-15T11:20:00Z',
    plano: mockPlanos[3]
  }
];

export const mockFaturas: Fatura[] = [
  {
    id: 1,
    cliente_id: 1,
    referencia: '2024-03-001',
    vencimento: '2024-03-15',
    valor: 199.90,
    status: 'pago',
    forma_pagamento: 'PIX',
    data_pagamento: '2024-03-14T10:30:00Z',
    criado_em: '2024-03-01T08:00:00Z',
    cliente: mockClientes[0]
  },
  {
    id: 2,
    cliente_id: 2,
    referencia: '2024-03-002',
    vencimento: '2024-03-20',
    valor: 399.90,
    status: 'pendente',
    criado_em: '2024-03-01T08:00:00Z',
    cliente: mockClientes[1]
  },
  {
    id: 3,
    cliente_id: 3,
    referencia: '2024-03-003',
    vencimento: '2024-03-10',
    valor: 99.90,
    status: 'vencido',
    criado_em: '2024-03-01T08:00:00Z',
    cliente: mockClientes[2]
  },
  {
    id: 4,
    cliente_id: 4,
    referencia: '2024-03-004',
    vencimento: '2024-03-25',
    valor: 699.90,
    status: 'pendente',
    criado_em: '2024-03-01T08:00:00Z',
    cliente: mockClientes[3]
  },
  {
    id: 5,
    cliente_id: 1,
    referencia: '2024-04-001',
    vencimento: '2024-04-15',
    valor: 199.90,
    status: 'pendente',
    criado_em: '2024-04-01T08:00:00Z',
    cliente: mockClientes[0]
  }
];

export const mockBackups: Backup[] = [
  {
    id: 1,
    cliente_id: 1,
    arquivo: 'backup_abc123_2024-03-15_02-00-00.sql',
    tamanho_mb: 45.2,
    status: 'concluido',
    tipo: 'automatico',
    criado_em: '2024-03-15T02:00:00Z',
    cliente: mockClientes[0]
  },
  {
    id: 2,
    cliente_id: 2,
    arquivo: 'backup_def456_2024-03-15_02-15-00.sql',
    tamanho_mb: 128.7,
    status: 'concluido',
    tipo: 'automatico',
    criado_em: '2024-03-15T02:15:00Z',
    cliente: mockClientes[1]
  },
  {
    id: 3,
    cliente_id: 1,
    arquivo: 'backup_abc123_2024-03-14_15-30-00.sql',
    tamanho_mb: 44.8,
    status: 'concluido',
    tipo: 'manual',
    criado_em: '2024-03-14T15:30:00Z',
    cliente: mockClientes[0]
  },
  {
    id: 4,
    cliente_id: 3,
    arquivo: 'backup_ghi789_2024-03-13_02-30-00.sql',
    tamanho_mb: 0,
    status: 'erro',
    tipo: 'automatico',
    criado_em: '2024-03-13T02:30:00Z',
    cliente: mockClientes[2]
  },
  {
    id: 5,
    cliente_id: 4,
    arquivo: 'backup_jkl012_2024-03-15_02-45-00.sql',
    tamanho_mb: 256.3,
    status: 'concluido',
    tipo: 'automatico',
    criado_em: '2024-03-15T02:45:00Z',
    cliente: mockClientes[3]
  },
  {
    id: 6,
    cliente_id: 2,
    arquivo: 'backup_def456_2024-03-16_10-15-00.sql',
    tamanho_mb: 0,
    status: 'processando',
    tipo: 'manual',
    criado_em: '2024-03-16T10:15:00Z',
    cliente: mockClientes[1]
  }
];

export const mockLogs: Log[] = [
  {
    id: 1,
    usuario_id: 1,
    acao: 'LOGIN',
    detalhes: 'Login realizado com sucesso',
    ip: '192.168.1.100',
    criado_em: '2024-03-15T08:30:00Z'
  },
  {
    id: 2,
    usuario_id: 1,
    acao: 'CRIAR_CLIENTE',
    detalhes: 'Cliente TechSolutions Ltda criado',
    ip: '192.168.1.100',
    criado_em: '2024-03-15T09:15:00Z'
  },
  {
    id: 3,
    usuario_id: 1,
    acao: 'GERAR_BACKUP',
    detalhes: 'Backup manual do cliente ABC123 iniciado',
    ip: '192.168.1.100',
    criado_em: '2024-03-15T10:00:00Z'
  },
  {
    id: 4,
    usuario_id: 1,
    acao: 'MARCAR_FATURA_PAGO',
    detalhes: 'Fatura 2024-03-001 marcada como paga',
    ip: '192.168.1.100',
    criado_em: '2024-03-15T11:30:00Z'
  }
];

export const mockUser: User = {
  id: 1,
  nome: 'Administrador',
  email: 'admin@nicebee.com.br',
  tipo: 'admin',
  ultimo_login: '2024-03-15T08:30:00Z',
  status: 'ativo',
  criado_em: '2024-01-01T00:00:00Z'
};

export const mockDashboardStats: DashboardStats = {
  total_clientes: mockClientes.length,
  clientes_ativos: mockClientes.filter(c => c.status === 'ativo').length,
  receita_mensal: mockFaturas.filter(f => f.status === 'pago').reduce((acc, f) => acc + f.valor, 0),
  uso_total_mb: mockClientes.reduce((acc, c) => acc + c.uso_mb, 0),
  faturas_pendentes: mockFaturas.filter(f => f.status === 'pendente' || f.status === 'vencido').length,
  backups_hoje: mockBackups.filter(b => {
    const hoje = new Date().toISOString().split('T')[0];
    return b.criado_em.startsWith(hoje);
  }).length
};

// Função para simular delay de API
export const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms));

// Função para simular resposta de API
export const mockApiResponse = async <T>(data: T, delayMs: number = 500): Promise<T> => {
  await delay(delayMs);
  return data;
};

// Função para gerar dados mock dinâmicos
export const generateMockData = () => {
  // Atualizar estatísticas baseadas nos dados atuais
  const stats: DashboardStats = {
    total_clientes: mockClientes.length,
    clientes_ativos: mockClientes.filter(c => c.status === 'ativo').length,
    receita_mensal: mockFaturas.filter(f => f.status === 'pago').reduce((acc, f) => acc + f.valor, 0),
    uso_total_mb: mockClientes.reduce((acc, c) => acc + c.uso_mb, 0),
    faturas_pendentes: mockFaturas.filter(f => f.status === 'pendente' || f.status === 'vencido').length,
    backups_hoje: mockBackups.filter(b => {
      const hoje = new Date().toISOString().split('T')[0];
      return b.criado_em.startsWith(hoje);
    }).length
  };
  
  return {
    clientes: mockClientes,
    planos: mockPlanos,
    faturas: mockFaturas,
    backups: mockBackups,
    logs: mockLogs,
    stats
  };
};