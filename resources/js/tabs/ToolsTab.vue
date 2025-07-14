<template>
  <div class="tools-container">
    <div class="tools-section" v-if="!hasApiKey">
      <h2 class="section-title">AI Configuration</h2>
      
      <form @submit.prevent="saveApiKey" class="api-key-form">
        <div class="form-section">
          <div class="form-row">
            <label class="form-label">Gemini API Key</label>
            <div class="api-key-input-group">
              <input 
                :type="showApiKey ? 'text' : 'password'"
                class="api-key-field" 
                v-model="apiKey" 
                placeholder="Enter your Gemini API key"
                :disabled="isSaving"
              />
              <button 
                type="button" 
                class="toggle-visibility-btn"
                @click="showApiKey = !showApiKey"
                :disabled="isSaving"
              >
                {{ showApiKey ? '🙈' : '👁️' }}
              </button>
            </div>
          </div>
          <small class="form-hint">
            Get your API key from <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>
          </small>
        </div>

        <div class="button-group">
          <button type="submit" class="save-btn" :disabled="isSaving || !apiKey.trim()">
            {{ isSaving ? 'Saving...' : 'Save API Key' }}
          </button>
          <button type="button" class="test-btn" @click="testApiKey" :disabled="isTesting || !apiKey.trim()">
            {{ isTesting ? 'Testing...' : 'Test Connection' }}
          </button>
        </div>
      </form>
    </div>

    <div class="tools-section" v-if="hasApiKey">
      <div class="api-configured-message">
        <h3>🤖 Gemini AI Configured</h3>
        <p>Your Gemini API key is configured and ready to use!</p>
        <ul>
          <li>✅ API connection verified</li>
          <li>🎯 Ready for AI content generation</li>
          <li>🚀 Go to "AI Generate" tab to create posts</li>
        </ul>
        <div class="navigate-hint">
          <p>Switch to the <strong>"AI Generate"</strong> tab to start creating AI-powered content with multiple topics, custom settings, and bulk generation.</p>
        </div>
        <div class="disconnect-section">
          <button type="button" class="disconnect-btn" @click="disconnectApi" :disabled="isDisconnecting">
            {{ isDisconnecting ? 'Disconnecting...' : 'Disconnect API' }}
          </button>
        </div>
      </div>
    </div>

    <div class="tools-section" v-if="!hasApiKey && !isLoading">
      <div class="no-api-key-message">
        <h3>🤖 AI-Powered Content Generation</h3>
        <p>Configure your Gemini API key above to unlock AI-powered post generation with:</p>
        <ul>
          <li>✨ Creative and unique titles</li>
          <li>📝 Contextual content generation</li>
          <li>🎯 Topic-specific posts</li>
          <li>🔄 Multiple writing styles</li>
          <li>📊 Bulk generation with progress tracking</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
import { useToast } from '../composables/useToast.js';

export default {
  name: "ToolsTab",
  setup() {
    const { success, error } = useToast();
    return { success, error };
  },
  data() {
    return {
      apiKey: '',
      showApiKey: false,
      hasApiKey: false,
      isLoading: false,
      isSaving: false,
      isTesting: false,
      isDisconnecting: false
    };
  },
  async mounted() {
    await this.loadApiKey();
  },
  methods: {
    async loadApiKey() {
      this.isLoading = true;
      try {
        const response = await fetch(window.SuitePressSettings.apiKeyUrl, {
          method: 'GET',
          headers: {
            'X-WP-Nonce': window.SuitePressSettings.nonce,
            'Content-Type': 'application/json'
          }
        });

        if (response.ok) {
          const result = await response.json();
          if (result.success && result.data) {
            this.hasApiKey = result.data.has_key;
            // Don't load the actual key for security reasons
          }
        }
      } catch (error) {
        console.error('Failed to load API key status:', error);
      } finally {
        this.isLoading = false;
      }
    },

    async saveApiKey() {
      if (!this.apiKey.trim()) {
        this.error('Please enter an API key');
        return;
      }

      this.isSaving = true;
      try {
        const response = await fetch(window.SuitePressSettings.apiKeyUrl, {
          method: 'POST',
          headers: {
            'X-WP-Nonce': window.SuitePressSettings.nonce,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ api_key: this.apiKey })
        });

        const result = await response.json();

        if (response.ok && result.success) {
          this.success('API key saved successfully!');
          this.hasApiKey = true;
          this.apiKey = ''; // Clear the input for security
        } else {
          const errorMessage = result.message || 'Failed to save API key';
          this.error(errorMessage);
        }
      } catch (error) {
        console.error('Failed to save API key:', error);
        this.error('Network error: Failed to save API key');
      } finally {
        this.isSaving = false;
      }
    },

    async testApiKey() {
      if (!this.apiKey.trim()) {
        this.error('Please enter an API key to test');
        return;
      }

      this.isTesting = true;
      try {
        const response = await fetch(window.SuitePressSettings.testApiKeyUrl, {
          method: 'POST',
          headers: {
            'X-WP-Nonce': window.SuitePressSettings.nonce,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ api_key: this.apiKey })
        });

        const result = await response.json();

        if (response.ok && result.success) {
          this.success('API key is valid and working!');
        } else {
          const errorMessage = result.message || 'API key test failed';
          this.error(errorMessage);
        }
      } catch (error) {
        console.error('Failed to test API key:', error);
        this.error('Network error: Failed to test API key');
      } finally {
        this.isTesting = false;
      }
    },

    async disconnectApi() {
      this.isDisconnecting = true;
      try {
        const response = await fetch(window.SuitePressSettings.apiKeyUrl, {
          method: 'POST',
          headers: {
            'X-WP-Nonce': window.SuitePressSettings.nonce,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ api_key: '' })
        });

        const result = await response.json();

        if (response.ok && result.success) {
          this.success('API key disconnected successfully!');
          this.hasApiKey = false;
          this.apiKey = '';
        } else {
          const errorMessage = result.message || 'Failed to disconnect API key';
          this.error(errorMessage);
        }
      } catch (error) {
        console.error('Failed to disconnect API key:', error);
        this.error('Network error: Failed to disconnect API key');
      } finally {
        this.isDisconnecting = false;
      }
    }
  }
};
</script>

<style scoped>
.tools-container {
  max-width: 800px;
  margin-top: 20px;
}

.tools-section {
  margin-bottom: 40px;
  background: #ffffff;
  border-radius: 12px;
}

.section-title {
  font-size: 20px;
  font-weight: 600;
  color: #111827;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 2px solid #e5e7eb;
}

.form-section {
  margin-bottom: 24px;
}

.form-row {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 8px;
}

.form-label {
  font-size: 15px;
  font-weight: 500;
  color: #374151;
  min-width: 150px;
  flex-shrink: 0;
}

.api-key-input-group {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
}

.api-key-field {
  flex: 1;
  padding: 10px 14px;
  border: 1.5px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
  color: #111827;
  background: #ffffff;
  transition: all 0.2s ease;
  font-family: 'Courier New', monospace;
}

.api-key-field:focus {
  outline: none;
  border-color: #008080;
  box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.1);
}

.toggle-visibility-btn {
  padding: 10px 12px;
  border: 1.5px solid #e5e7eb;
  border-radius: 6px;
  background: #f9fafb;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 16px;
}

.toggle-visibility-btn:hover {
  background: #f3f4f6;
  border-color: #d1d5db;
}

.text-field, .select-field {
  flex: 1;
  padding: 10px 14px;
  border: 1.5px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
  color: #111827;
  background: #ffffff;
  transition: all 0.2s ease;
}

.text-field:focus, .select-field:focus {
  outline: none;
  border-color: #008080;
  box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.1);
}

.form-hint {
  color: #6b7280;
  font-size: 13px;
  line-height: 1.4;
  display: block;
}

.form-hint a {
  color: #008080;
  text-decoration: none;
}

.form-hint a:hover {
  text-decoration: underline;
}

.button-group {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

.save-btn, .test-btn, .generate-ai-btn {
  padding: 10px 24px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.save-btn {
  background: linear-gradient(135deg, #008080 0%, #006666 100%);
  color: white;
  box-shadow: 0 2px 4px rgba(0, 128, 128, 0.2);
}

.save-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #006666 0%, #004d4d 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 128, 128, 0.3);
}

.test-btn {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: white;
  box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
}

.test-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
}

.generate-ai-btn {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
}

.generate-ai-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(245, 158, 11, 0.3);
}

.save-btn:disabled, .test-btn:disabled, .generate-ai-btn:disabled {
  background: #d1d5db;
  color: #9ca3af;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.no-api-key-message, .api-configured-message {
  text-align: center;
  padding: 32px;
  border-radius: 8px;
  border: 1px solid;
}

.no-api-key-message {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border-color: #bae6fd;
}

.api-configured-message {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border-color: #bbf7d0;
}

.no-api-key-message h3, .api-configured-message h3 {
  margin-bottom: 16px;
  font-size: 18px;
}

.no-api-key-message h3 {
  color: #0369a1;
}

.api-configured-message h3 {
  color: #16a34a;
}

.no-api-key-message p, .api-configured-message p {
  margin-bottom: 16px;
}

.no-api-key-message p {
  color: #075985;
}

.api-configured-message p {
  color: #15803d;
}

.no-api-key-message ul, .api-configured-message ul {
  list-style: none;
  padding: 0;
  margin-bottom: 20px;
}

.no-api-key-message ul {
  color: #0c4a6e;
}

.api-configured-message ul {
  color: #14532d;
}

.no-api-key-message li, .api-configured-message li {
  margin-bottom: 8px;
  font-weight: 500;
}

.navigate-hint {
  margin-top: 20px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 8px;
  border: 1px solid #a7f3d0;
}

.navigate-hint p {
  color: #047857;
  font-weight: 600;
  margin: 0;
}

.disconnect-section {
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #a7f3d0;
}

.disconnect-btn {
  padding: 8px 20px;
  border: 1.5px solid #dc2626;
  border-radius: 6px;
  background: #ffffff;
  color: #dc2626;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.disconnect-btn:hover:not(:disabled) {
  background: #dc2626;
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);
}

.disconnect-btn:disabled {
  background: #f3f4f6;
  color: #9ca3af;
  border-color: #d1d5db;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}
</style>
