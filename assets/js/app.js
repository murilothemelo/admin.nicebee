/**
 * JavaScript principal do sistema
 */

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Configurar tooltips
    initTooltips();
    
    // Configurar modais
    initModals();
    
    // Configurar notificações
    initNotifications();
    
    // Auto-refresh para dashboard
    if (window.location.search.includes('page=dashboard') || window.location.search === '') {
        setInterval(updateDashboardStats, 30000); // 30 segundos
    }
}

// Tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[title]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(event) {
    const element = event.target;
    const title = element.getAttribute('title');
    
    if (!title) return;
    
    const tooltip = document.createElement('div');
    tooltip.className = 'absolute bg-gray-800 text-white text-xs px-2 py-1 rounded shadow-lg z-50';
    tooltip.textContent = title;
    tooltip.id = 'tooltip';
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
    
    element.removeAttribute('title');
    element.setAttribute('data-original-title', title);
}

function hideTooltip(event) {
    const element = event.target;
    const tooltip = document.getElementById('tooltip');
    
    if (tooltip) {
        tooltip.remove();
    }
    
    const originalTitle = element.getAttribute('data-original-title');
    if (originalTitle) {
        element.setAttribute('title', originalTitle);
        element.removeAttribute('data-original-title');
    }
}

// Modais
function initModals() {
    // Fechar modal ao clicar fora
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal-backdrop')) {
            closeModal(event.target.closest('.modal'));
        }
    });
    
    // Fechar modal com ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal:not(.hidden)');
            if (openModal) {
                closeModal(openModal);
            }
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modal) {
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
}

// Notificações
function initNotifications() {
    // Remover notificações automaticamente após 5 segundos
    const notifications = document.querySelectorAll('.notification');
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    });
}

function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `notification p-4 rounded-lg shadow-lg ${getNotificationClass(type)}`;
    
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="${getNotificationIcon(type)} mr-2"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-lg">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remover automaticamente
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function getNotificationClass(type) {
    const classes = {
        success: 'bg-green-100 border border-green-200 text-green-800',
        error: 'bg-red-100 border border-red-200 text-red-800',
        warning: 'bg-yellow-100 border border-yellow-200 text-yellow-800',
        info: 'bg-blue-100 border border-blue-200 text-blue-800'
    };
    return classes[type] || classes.info;
}

function getNotificationIcon(type) {
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    return icons[type] || icons.info;
}

// Dashboard
function updateDashboardStats() {
    fetch('api/dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatsCards(data.stats);
            }
        })
        .catch(error => console.error('Erro ao atualizar stats:', error));
}

function updateStatsCards(stats) {
    // Atualizar cards de estatísticas
    const elements = {
        'clientes-ativos': stats.clientes_ativos,
        'receita-mensal': formatCurrency(stats.receita_mensal),
        'uso-disco': (stats.uso_total_mb / 1024).toFixed(1) + ' GB',
        'alertas': stats.faturas_pendentes
    };
    
    Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    });
}

// Utilitários
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('pt-BR');
}

function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString('pt-BR');
}

// Confirmações
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Loading states
function showLoading(element) {
    const originalContent = element.innerHTML;
    element.setAttribute('data-original-content', originalContent);
    element.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Carregando...';
    element.disabled = true;
}

function hideLoading(element) {
    const originalContent = element.getAttribute('data-original-content');
    if (originalContent) {
        element.innerHTML = originalContent;
        element.removeAttribute('data-original-content');
    }
    element.disabled = false;
}

// Formulários
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-300');
            isValid = false;
        } else {
            field.classList.remove('border-red-300');
        }
    });
    
    return isValid;
}

// Busca em tempo real
function setupLiveSearch(inputId, targetSelector) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    input.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const targets = document.querySelectorAll(targetSelector);
        
        targets.forEach(target => {
            const text = target.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                target.style.display = '';
            } else {
                target.style.display = 'none';
            }
        });
    });
}

// Copiar para clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('success', 'Copiado para a área de transferência!');
    }).catch(() => {
        showNotification('error', 'Erro ao copiar para a área de transferência');
    });
}

// Auto-save para formulários
function setupAutoSave(formId, saveUrl) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            const formData = new FormData(form);
            
            fetch(saveUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Salvo automaticamente');
                }
            })
            .catch(error => {
                console.error('Erro no auto-save:', error);
            });
        });
    });
}

// Drag and drop para upload de arquivos
function setupFileUpload(dropZoneId, inputId, callback) {
    const dropZone = document.getElementById(dropZoneId);
    const input = document.getElementById(inputId);
    
    if (!dropZone || !input) return;
    
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('border-blue-500', 'bg-blue-50');
    });
    
    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('border-blue-500', 'bg-blue-50');
    });
    
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('border-blue-500', 'bg-blue-50');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            input.files = files;
            if (callback) callback(files);
        }
    });
    
    dropZone.addEventListener('click', function() {
        input.click();
    });
    
    input.addEventListener('change', function() {
        if (this.files.length > 0 && callback) {
            callback(this.files);
        }
    });
}

// Exportar dados
function exportData(data, filename, type = 'json') {
    let content, mimeType;
    
    switch (type) {
        case 'csv':
            content = convertToCSV(data);
            mimeType = 'text/csv';
            break;
        case 'json':
        default:
            content = JSON.stringify(data, null, 2);
            mimeType = 'application/json';
            break;
    }
    
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function convertToCSV(data) {
    if (!Array.isArray(data) || data.length === 0) return '';
    
    const headers = Object.keys(data[0]);
    const csvContent = [
        headers.join(','),
        ...data.map(row => headers.map(header => `"${row[header] || ''}"`).join(','))
    ].join('\n');
    
    return csvContent;
}

// Tema escuro/claro
function toggleTheme() {
    const body = document.body;
    const isDark = body.classList.contains('dark-mode');
    
    if (isDark) {
        body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
    } else {
        body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
    }
}

// Aplicar tema salvo
function applyStoredTheme() {
    const storedTheme = localStorage.getItem('theme');
    if (storedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
}

// Aplicar tema na inicialização
applyStoredTheme();

// Funções globais para uso nos templates
window.showNotification = showNotification;
window.confirmAction = confirmAction;
window.copyToClipboard = copyToClipboard;
window.exportData = exportData;
window.toggleTheme = toggleTheme;