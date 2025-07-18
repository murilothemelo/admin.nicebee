<?php
/**
 * Funções Auxiliares
 */

function formatCurrency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function generateUniqueCode($length = 6) {
    global $pdo;
    
    do {
        $code = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE codigo_cliente = ?");
        $stmt->execute([$code]);
    } while ($stmt->rowCount() > 0);
    
    return $code;
}

function getStatusBadge($status) {
    $badges = [
        'ativo' => 'bg-green-100 text-green-800',
        'inativo' => 'bg-gray-100 text-gray-800',
        'bloqueado' => 'bg-red-100 text-red-800',
        'pendente' => 'bg-yellow-100 text-yellow-800',
        'pago' => 'bg-green-100 text-green-800',
        'vencido' => 'bg-red-100 text-red-800',
        'cancelado' => 'bg-gray-100 text-gray-800',
        'concluido' => 'bg-green-100 text-green-800',
        'processando' => 'bg-yellow-100 text-yellow-800',
        'erro' => 'bg-red-100 text-red-800'
    ];
    
    $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
    return "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$class}'>" . ucfirst($status) . "</span>";
}

function getStatusIcon($status) {
    $icons = [
        'ativo' => 'fas fa-check-circle text-green-500',
        'inativo' => 'fas fa-times-circle text-gray-500',
        'bloqueado' => 'fas fa-exclamation-circle text-red-500',
        'pendente' => 'fas fa-clock text-yellow-500',
        'pago' => 'fas fa-check-circle text-green-500',
        'vencido' => 'fas fa-exclamation-triangle text-red-500',
        'cancelado' => 'fas fa-times-circle text-gray-500',
        'concluido' => 'fas fa-check-circle text-green-500',
        'processando' => 'fas fa-clock text-yellow-500',
        'erro' => 'fas fa-times-circle text-red-500'
    ];
    
    $icon = $icons[$status] ?? 'fas fa-question-circle text-gray-500';
    return "<i class='{$icon}'></i>";
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function showAlert($type, $message) {
    $colors = [
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800'
    ];
    
    $icons = [
        'success' => 'fas fa-check-circle',
        'error' => 'fas fa-exclamation-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'info' => 'fas fa-info-circle'
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    $icon = $icons[$type] ?? $icons['info'];
    
    return "
    <div class='rounded-lg p-4 mb-4 border {$color}'>
        <div class='flex items-center'>
            <i class='{$icon} mr-2'></i>
            <span class='text-sm font-medium'>{$message}</span>
        </div>
    </div>";
}