<template>
  <Teleport to="body">
    <div class="toast-container">
      <Toast
        v-for="toast in toasts"
        :key="toast.id"
        :message="toast.message"
        :type="toast.type"
        :duration="toast.duration"
        :persistent="toast.persistent"
        @close="removeToast(toast.id)"
      />
    </div>
  </Teleport>
</template>

<script>
import Toast from './Toast.vue';
import { useToast } from '../composables/useToast.js';

export default {
  name: 'ToastContainer',
  components: {
    Toast
  },
  setup() {
    const { toasts, removeToast } = useToast();
    
    return {
      toasts,
      removeToast
    };
  }
};
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 40px;
  right: 20px;
  z-index: 9999;
  pointer-events: none;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.toast-container > * {
  pointer-events: auto;
}
</style>
