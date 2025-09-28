import { useEffect, useCallback } from 'react';
import { useAppStore } from '../stores/useAppStore';

/**
 * Hook for keyboard navigation shortcuts
 */
export const useKeyboardNavigation = () => {
  const { 
    currentSessionId, 
    setCurrentSession, 
    chatSessions,
    getCurrentSession,
  } = useAppStore();

  const handleKeyNavigation = useCallback((event: KeyboardEvent) => {
    // Only handle keyboard shortcuts when not in input fields
    const isInInputField = ['input', 'textarea', 'select'].includes(
      (event.target as HTMLElement)?.tagName?.toLowerCase() || ''
    );
    
    if (isInInputField) return;

    // Cmd/Ctrl + K for command palette (future feature)
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
      event.preventDefault();
      // TODO: Open command palette
      console.log('Command palette shortcut triggered');
      return;
    }

    // Cmd/Ctrl + N for new chat
    if ((event.metaKey || event.ctrlKey) && event.key === 'n') {
      event.preventDefault();
      // TODO: Trigger new chat creation
      console.log('New chat shortcut triggered');
      return;
    }

    // Arrow keys for session navigation
    if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
      if (chatSessions.length === 0) return;
      
      const currentIndex = currentSessionId 
        ? chatSessions.findIndex(s => s.id === currentSessionId)
        : -1;
      
      let nextIndex: number;
      
      if (event.key === 'ArrowUp') {
        nextIndex = currentIndex <= 0 ? chatSessions.length - 1 : currentIndex - 1;
      } else {
        nextIndex = currentIndex >= chatSessions.length - 1 ? 0 : currentIndex + 1;
      }
      
      if (nextIndex >= 0 && nextIndex < chatSessions.length) {
        event.preventDefault();
        setCurrentSession(chatSessions[nextIndex].id);
      }
    }

    // Escape to clear selection (if needed)
    if (event.key === 'Escape') {
      // TODO: Handle escape actions like closing modals
      console.log('Escape pressed');
    }
  }, [currentSessionId, setCurrentSession, chatSessions]);

  useEffect(() => {
    document.addEventListener('keydown', handleKeyNavigation);
    
    return () => {
      document.removeEventListener('keydown', handleKeyNavigation);
    };
  }, [handleKeyNavigation]);

  // Helper for focus management
  const focusElement = useCallback((selector: string) => {
    const element = document.querySelector(selector) as HTMLElement;
    if (element) {
      element.focus();
    }
  }, []);

  return {
    focusElement,
    getCurrentSession,
  };
};

/**
 * Hook for managing ARIA announcements for screen readers
 */
export const useScreenReaderAnnouncements = () => {
  const announce = useCallback((message: string, priority: 'polite' | 'assertive' = 'polite') => {
    // Create or update live region for screen reader announcements
    let liveRegion = document.getElementById('sr-live-region');
    
    if (!liveRegion) {
      liveRegion = document.createElement('div');
      liveRegion.id = 'sr-live-region';
      liveRegion.setAttribute('aria-live', priority);
      liveRegion.setAttribute('aria-atomic', 'true');
      liveRegion.className = 'sr-only';
      liveRegion.style.cssText = `
        position: absolute;
        left: -10000px;
        width: 1px;
        height: 1px;
        overflow: hidden;
      `;
      document.body.appendChild(liveRegion);
    }

    // Update the live region content
    liveRegion.textContent = message;
    
    // Clear after announcement to allow repeat announcements
    setTimeout(() => {
      if (liveRegion) {
        liveRegion.textContent = '';
      }
    }, 1000);
  }, []);

  const announceSessionSwitch = useCallback((sessionTitle: string) => {
    announce(`Switched to chat: ${sessionTitle}`, 'polite');
  }, [announce]);

  const announceError = useCallback((errorMessage: string) => {
    announce(`Error: ${errorMessage}`, 'assertive');
  }, [announce]);

  const announceSuccess = useCallback((successMessage: string) => {
    announce(successMessage, 'polite');
  }, [announce]);

  return {
    announce,
    announceSessionSwitch,
    announceError,
    announceSuccess,
  };
};