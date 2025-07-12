import { ref, reactive } from 'vue';

// Global toast state - shared across all components
const toastState = reactive({
  toasts: [],
  nextId: 0,
  maxToasts: 5 // Limit the number of toasts shown at once
});

export function useToast() {
  const addToast = (message, type = 'info', options = {}) => {
    const id = ++toastState.nextId;
    const toast = {
      id,
      message,
      type,
      duration: 4000,
      persistent: false,
      createdAt: Date.now(),
      ...options
    };
    
    // Add new toast to the end of the array
    toastState.toasts.push(toast);
    
    // If we exceed the max number of toasts, remove the oldest one
    if (toastState.toasts.length > toastState.maxToasts) {
      toastState.toasts.shift(); // Remove from beginning (FIFO)
    }
    
    // Auto-remove this toast after its duration (FIFO order maintained)
    if (!toast.persistent && toast.duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, toast.duration);
    }
    
    // Return a function to remove this specific toast
    return () => removeToast(id);
  };

  const removeToast = (id) => {
    const index = toastState.toasts.findIndex(toast => toast.id === id);
    if (index > -1) {
      toastState.toasts.splice(index, 1);
    }
  };

  const success = (message, options = {}) => {
    return addToast(message, 'success', options);
  };

  const error = (message, options = {}) => {
    return addToast(message, 'error', options);
  };

  const info = (message, options = {}) => {
    return addToast(message, 'info', options);
  };

  const warning = (message, options = {}) => {
    return addToast(message, 'warning', options);
  };

  const clear = () => {
    toastState.toasts = [];
  };

  return {
    toasts: toastState.toasts,
    addToast,
    removeToast,
    success,
    error,
    info,
    warning,
    clear
  };
}
