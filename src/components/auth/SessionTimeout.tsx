import React, { useEffect, useState } from 'react';
import { useAdmin } from '../../contexts/AdminContext';
import { Modal } from '../common/Modal';
import { Button } from '../common/Button';
import { Clock, AlertTriangle } from 'lucide-react';

export function SessionTimeout() {
  const { auth, logout } = useAdmin();
  const [showWarning, setShowWarning] = useState(false);
  const [timeLeft, setTimeLeft] = useState(0);

  useEffect(() => {
    if (!auth.isAuthenticated) return;

    // Configurar timeout de sessão (30 minutos)
    const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutos
    const WARNING_TIME = 5 * 60 * 1000; // 5 minutos antes

    let warningTimer: NodeJS.Timeout;
    let logoutTimer: NodeJS.Timeout;
    let countdownTimer: NodeJS.Timeout;

    const resetTimers = () => {
      clearTimeout(warningTimer);
      clearTimeout(logoutTimer);
      clearTimeout(countdownTimer);

      // Timer para mostrar aviso
      warningTimer = setTimeout(() => {
        setShowWarning(true);
        setTimeLeft(WARNING_TIME / 1000);

        // Countdown
        countdownTimer = setInterval(() => {
          setTimeLeft(prev => {
            if (prev <= 1) {
              clearInterval(countdownTimer);
              return 0;
            }
            return prev - 1;
          });
        }, 1000);
      }, SESSION_TIMEOUT - WARNING_TIME);

      // Timer para logout automático
      logoutTimer = setTimeout(() => {
        logout();
      }, SESSION_TIMEOUT);
    };

    const handleActivity = () => {
      if (showWarning) {
        setShowWarning(false);
      }
      resetTimers();
    };

    // Eventos que resetam o timer
    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
    
    events.forEach(event => {
      document.addEventListener(event, handleActivity, true);
    });

    resetTimers();

    return () => {
      events.forEach(event => {
        document.removeEventListener(event, handleActivity, true);
      });
      clearTimeout(warningTimer);
      clearTimeout(logoutTimer);
      clearTimeout(countdownTimer);
    };
  }, [auth.isAuthenticated, logout, showWarning]);

  const extendSession = () => {
    setShowWarning(false);
    // Aqui você pode fazer uma chamada para a API para renovar o token
  };

  const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  return (
    <Modal
      isOpen={showWarning}
      onClose={() => {}}
      title="Sessão Expirando"
      maxWidth="sm"
    >
      <div className="text-center">
        <div className="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <AlertTriangle className="w-8 h-8 text-orange-600" />
        </div>
        
        <h3 className="text-lg font-semibold text-gray-900 mb-2">
          Sua sessão está prestes a expirar
        </h3>
        
        <p className="text-gray-600 mb-4">
          Por motivos de segurança, você será desconectado automaticamente em:
        </p>
        
        <div className="bg-orange-50 rounded-lg p-4 mb-6">
          <div className="flex items-center justify-center text-orange-800">
            <Clock className="w-5 h-5 mr-2" />
            <span className="text-2xl font-bold font-mono">
              {formatTime(timeLeft)}
            </span>
          </div>
        </div>
        
        <div className="flex space-x-3">
          <Button
            variant="secondary"
            onClick={logout}
            className="flex-1"
          >
            Sair Agora
          </Button>
          <Button
            variant="primary"
            onClick={extendSession}
            className="flex-1"
          >
            Continuar Sessão
          </Button>
        </div>
      </div>
    </Modal>
  );
}