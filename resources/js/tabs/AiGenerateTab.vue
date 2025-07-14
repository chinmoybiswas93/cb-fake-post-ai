<template>
  <div class="ai-generate-container">
    <div class="ai-generate-section">
      <h2 class="section-title">AI Content Generation</h2>

      <form @submit.prevent="generateAIPosts" class="generation-form">
        <!-- Topics Section -->
        <div class="form-section">
          <div class="form-row content-topics-row">
            <label class="form-label">Content Topics</label>
            <div class="topics-container">
              <div v-for="(topic, index) in topics" :key="index" class="topic-input-group">
                <input type="text" class="topic-field" v-model="topics[index]"
                  :placeholder="`Topic ${index + 1} (e.g., Technology trends, Health tips)`" :disabled="isGenerating" />
                <button type="button" class="add-topic-btn" @click="addTopic" v-if="index === topics.length - 1"
                  :disabled="isGenerating || topics.length >= 10">
                  +
                </button>
                <button type="button" class="remove-topic-btn" @click="removeTopic(index)" v-if="topics.length > 1"
                  :disabled="isGenerating">
                  ✕
                </button>
              </div>
            </div>
          </div>
          <small class="form-hint content-topics-hint">
            Add multiple topics to generate varied content. Each topic will create posts based on your settings below.
          </small>
        </div>

        <!-- Generation Settings -->
        <div class="settings-grid">
          <!-- First Row: AI Model and Content Style -->
          <div class="settings-row-2 ai-model-row">
            <!-- AI Model Selection -->
            <div class="form-section model-selection">
              <label class="form-label">AI Model</label>
              <select class="select-field" v-model="selectedModel" :disabled="isGenerating || isLoadingModels">
                <option v-if="isLoadingModels" value="">Loading models...</option>
                <option v-else-if="availableModels.length === 0" value="">No models available</option>
                <option v-else v-for="model in availableModels" :key="model.id" :value="model.id">
                  {{ model.name }}
                </option>
              </select>
            </div>

            <!-- Content Style -->
            <div class="form-section content-style-selection">
              <label class="form-label">Content Style</label>
              <select class="select-field" v-model="contentStyle" :disabled="isGenerating">
                <option value="informative">Informative</option>
                <option value="casual">Casual</option>
                <option value="professional">Professional</option>
                <option value="creative">Creative</option>
                <option value="technical">Technical</option>
              </select>
            </div>

            <div>
              <small class="form-hint model-hint">
                <span v-if="isLoadingModels">Fetching available models for your API key...</span>
                <span v-else-if="selectedModelDescription">{{ selectedModelDescription }}</span>
                <span v-else>Models are loaded dynamically based on your API key access.</span>
              </small>
            </div>
          </div>

          <!-- Second Row: Posts per Topic, Title Length, Content Length -->
          <div class="settings-row-3 post-topics-row">
            <!-- Number of Posts per Topic -->
            <div class="form-section">
              <label class="form-label">Posts per Topic</label>
              <div class="range-container">
                <input type="range" class="range-slider" v-model="postsPerTopic" min="1" max="10"
                  :disabled="isGenerating" />
                <span class="range-value">{{ postsPerTopic }}</span>
              </div>
            </div>

            <!-- Title Length -->
            <div class="form-section">
              <label class="form-label">Title Length (words)</label>
              <div class="range-container">
                <input type="range" class="range-slider" v-model="titleLength" min="3" max="15"
                  :disabled="isGenerating" />
                <span class="range-value">{{ titleLength }}</span>
              </div>
            </div>

            <!-- Content Length -->
            <div class="form-section">
              <label class="form-label">Content Length (paragraphs)</label>
              <div class="range-container">
                <input type="range" class="range-slider" v-model="contentLength" min="1" max="8"
                  :disabled="isGenerating" />
                <span class="range-value">{{ contentLength }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Categories Section -->
        <div class="form-section">
          <div class="form-row">
            <label class="form-label">Categories</label>
            <div class="categories-grid">
              <label v-for="category in categories" :key="category.id" class="category-checkbox">
                <input type="checkbox" :value="category.id" v-model="selectedCategories" :disabled="isGenerating" />
                <span class="category-name">{{ category.name }}</span>
                <span class="category-count">({{ category.count }})</span>
              </label>
            </div>
          </div>
          <small class="form-hint">
            Select categories for the generated posts. If none selected, posts will be uncategorized.
          </small>
        </div>

        <!-- Generation Summary -->
        <div class="generation-summary">
          <div class="summary-card">
            <h3>Generation Summary</h3>
            <div class="summary-details">
              <div class="summary-item">
                <span class="summary-label">AI Model:</span>
                <span class="summary-value">{{ selectedModelName }}</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Total Topics:</span>
                <span class="summary-value">{{ validTopics.length }}</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Posts per Topic:</span>
                <span class="summary-value">{{ postsPerTopic }}</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Total Posts:</span>
                <span class="summary-value">{{ totalPosts }}</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Selected Categories:</span>
                <span class="summary-value">{{ selectedCategoryNames.length || 'None' }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Generate Button -->
        <div class="button-group">
          <button type="submit" class="generate-btn" :disabled="isGenerating || !canGenerate">
            {{ isGenerating ? 'Generating...' : `Generate ${totalPosts} AI Posts` }}
          </button>
        </div>
      </form>

      <!-- Progress Section -->
      <div v-if="isGenerating" class="progress-section">
        <div class="progress-header">
          <h3>Generating Posts...</h3>
          <span class="progress-text">{{ progressText }}</span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: progressPercentage + '%' }"></div>
        </div>
        <div class="current-topic" v-if="currentTopic">
          <span>Current topic: <strong>{{ currentTopic }}</strong></span>
        </div>
      </div>

      <!-- Results Section -->
      <div v-if="generationResults.length > 0" class="results-section">
        <h3>Generation Results</h3>
        <div class="results-grid">
          <div v-for="result in generationResults" :key="result.topic" class="result-card"
            :class="{ 'result-success': result.success, 'result-error': !result.success }">
            <div class="result-header">
              <h4>{{ result.topic }}</h4>
              <span class="result-status">
                {{ result.success ? '✅' : '❌' }}
                {{ result.success ? `${result.postsCreated} posts` : 'Failed' }}
              </span>
            </div>
            <div class="result-message">{{ result.message }}</div>
            <div v-if="result.posts && result.posts.length > 0" class="result-posts">
              <div v-for="post in result.posts" :key="post.id" class="result-post">
                <a :href="post.url" target="_blank" class="post-link">{{ post.title }}</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useToast } from '../composables/useToast.js';

export default {
  name: "AiGenerateTab",
  setup() {
    const { success, error, info } = useToast();
    return { success, error, info };
  },
  data() {
    return {
      topics: [''],
      contentStyle: 'informative',
      selectedModel: 'gemini-1.5-flash-latest',
      availableModels: [],
      isLoadingModels: false,
      postsPerTopic: 1,
      titleLength: 5,
      contentLength: 3,
      selectedCategories: [],
      categories: [],
      isGenerating: false,
      hasApiKey: false,
      isLoading: false,

      // Progress tracking
      progressText: '',
      progressPercentage: 0,
      currentTopic: '',

      // Results
      generationResults: []
    };
  },
  computed: {
    validTopics() {
      return this.topics.filter(topic => topic.trim().length > 0);
    },
    totalPosts() {
      return this.validTopics.length * this.postsPerTopic;
    },
    canGenerate() {
      return this.hasApiKey && this.validTopics.length > 0 && !this.isGenerating;
    },
    selectedCategoryNames() {
      return this.categories
        .filter(cat => this.selectedCategories.includes(cat.id))
        .map(cat => cat.name);
    },
    selectedModelDescription() {
      const model = this.availableModels.find(m => m.id === this.selectedModel);
      return model ? model.description : '';
    },
    selectedModelName() {
      const model = this.availableModels.find(m => m.id === this.selectedModel);
      return model ? model.name : this.selectedModel;
    }
  },
  async mounted() {
    await this.loadInitialData();
  },
  methods: {
    async loadInitialData() {
      this.isLoading = true;
      try {
        await Promise.all([
          this.loadApiKeyStatus(),
          this.loadCategories()
        ]);

        // Load models only if API key is available
        if (this.hasApiKey) {
          await this.loadAvailableModels();
        }
      } catch (error) {
        console.error('Failed to load initial data:', error);
        this.error('Failed to load initial data');
      } finally {
        this.isLoading = false;
      }
    },

    async loadApiKeyStatus() {
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
          }
        }
      } catch (error) {
        console.error('Failed to load API key status:', error);
      }
    },

    async loadCategories() {
      try {
        const response = await fetch(window.SuitePressSettings.categoriesUrl, {
          method: 'GET',
          headers: {
            'X-WP-Nonce': window.SuitePressSettings.nonce,
            'Content-Type': 'application/json'
          }
        });

        if (response.ok) {
          const result = await response.json();
          if (result.success && result.data) {
            this.categories = result.data;
          }
        }
      } catch (error) {
        console.error('Failed to load categories:', error);
      }
    },

    async loadAvailableModels() {
      this.isLoadingModels = true;
      try {
        const response = await fetch(window.SuitePressSettings.availableModelsUrl, {
          method: 'GET',
          headers: {
            'X-WP-Nonce': window.SuitePressSettings.nonce,
            'Content-Type': 'application/json'
          }
        });

        if (response.ok) {
          const result = await response.json();
          if (result.success && result.data) {
            this.availableModels = result.data;

            // Set default model if current selection is not available
            if (this.availableModels.length > 0) {
              const currentModelExists = this.availableModels.some(m => m.id === this.selectedModel);
              if (!currentModelExists) {
                this.selectedModel = this.availableModels[0].id;
              }
            }
          } else {
            console.error('Failed to load models:', result.message);
            this.error('Failed to load available AI models');
          }
        } else {
          console.error('Failed to fetch models, status:', response.status);
          this.error('Failed to fetch available models');
        }
      } catch (error) {
        console.error('Error loading models:', error);
        this.error('Error loading available models');
      } finally {
        this.isLoadingModels = false;
      }
    },

    addTopic() {
      if (this.topics.length < 10) {
        this.topics.push('');
      }
    },

    removeTopic(index) {
      if (this.topics.length > 1) {
        this.topics.splice(index, 1);
      }
    },

    async generateAIPosts() {
      if (!this.canGenerate) {
        this.error('Cannot generate posts. Please check your configuration.');
        return;
      }

      this.isGenerating = true;
      this.generationResults = [];
      this.progressPercentage = 0;

      const totalTopics = this.validTopics.length;
      let completedTopics = 0;

      try {
        for (let i = 0; i < this.validTopics.length; i++) {
          const topic = this.validTopics[i];
          this.currentTopic = topic;
          this.progressText = `Processing topic ${i + 1} of ${totalTopics}: ${topic}`;

          try {
            const result = await this.generatePostsForTopic(topic);
            this.generationResults.push(result);

            if (result.success) {
              this.info(`Generated ${result.postsCreated} posts for "${topic}"`);
            } else {
              // Check if it's a model overload error and suggest alternatives
              if (result.message.includes('overloaded') || result.message.includes('quota exceeded')) {
                this.error(`Model "${this.selectedModel}" is overloaded for "${topic}". Try switching to a different model in the settings above.`);
              } else {
                this.error(`Failed to generate posts for "${topic}": ${result.message}`);
              }
            }
          } catch (error) {
            console.error(`Error generating posts for topic "${topic}":`, error);
            this.generationResults.push({
              topic: topic,
              success: false,
              message: 'Network error or unexpected failure',
              postsCreated: 0,
              posts: []
            });
            this.error(`Error generating posts for "${topic}"`);
          }

          completedTopics++;
          this.progressPercentage = (completedTopics / totalTopics) * 100;
        }

        // Final summary
        const totalSuccess = this.generationResults.filter(r => r.success).length;
        const totalPostsCreated = this.generationResults.reduce((sum, r) => sum + r.postsCreated, 0);

        if (totalSuccess > 0) {
          this.success(`Generation complete! Created ${totalPostsCreated} posts across ${totalSuccess} topics.`);
        } else {
          this.error('Generation failed for all topics. Please check your API key and try again.');
        }

      } catch (error) {
        console.error('Generation process failed:', error);
        this.error('Generation process failed unexpectedly');
      } finally {
        this.isGenerating = false;
        this.currentTopic = '';
        this.progressText = '';
        this.progressPercentage = 0;
      }
    },

    async generatePostsForTopic(topic) {
      try {
        const response = await fetch(window.SuitePressSettings.generateAiPostsUrl, {
          method: 'POST',
          headers: {
            'X-WP-Nonce': window.SuitePressSettings.nonce,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            topic: topic,
            style: this.contentStyle,
            model: this.selectedModel,
            settings: {
              posts_count: this.postsPerTopic,
              title_words: this.titleLength,
              content_paragraphs: this.contentLength,
              selected_categories: this.selectedCategories
            }
          })
        });

        const result = await response.json();

        if (response.ok && result.success) {
          return {
            topic: topic,
            success: true,
            message: result.message,
            postsCreated: result.data.success_count || 0,
            posts: result.data.created_posts || []
          };
        } else {
          return {
            topic: topic,
            success: false,
            message: result.message || 'Unknown error occurred',
            postsCreated: 0,
            posts: []
          };
        }
      } catch (error) {
        return {
          topic: topic,
          success: false,
          message: 'Network error: ' + error.message,
          postsCreated: 0,
          posts: []
        };
      }
    }
  }
};
</script>

<style scoped>
.section-title {
  font-size: 20px;
  font-weight: 600;
  color: #111827;
  margin-bottom: 20px;
  padding-bottom: 8px;
  border-bottom: 2px solid #e5e7eb;
}

.generation-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.form-row.content-topics-row {
  align-items: baseline;
}

.topics-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.topic-input-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.topic-field {
  flex: 1;
  padding: 8px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
  color: #111827;
  background: #ffffff;
  transition: all 0.2s ease;
}

.topic-field:focus {
  outline: none;
  border-color: #008080;
  box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.1);
}

.remove-topic-btn {
  padding: 6px 8px;
  border: 2px solid #ef4444;
  border-radius: 6px;
  background: #fef2f2;
  color: #dc2626;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 600;
  font-size: 12px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 32px;
}

.remove-topic-btn:hover:not(:disabled) {
  background: #ef4444;
  color: white;
}

.add-topic-btn {
  padding: 6px 8px;
  border: 2px solid #008080;
  border-radius: 6px;
  background: #f0fdfa;
  color: #008080;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 600;
  font-size: 16px;
  height: 32px;
  width: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 32px;
}

.add-topic-btn:hover:not(:disabled) {
  background: #008080;
  color: white;
}

.settings-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.settings-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 5px;
}

.settings-row-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 16px;
}

.settings-row-2.ai-model-row {
  align-items: start;
}

.model-selection,
.content-style-selection {
  flex-direction: row !important;
  align-items: center !important;
}

.post-topics-row {
  margin: 15px 0;
}

@media (max-width: 768px) {
  .settings-row-2 {
    grid-template-columns: 1fr;
  }

  .settings-row-3 {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 1024px) and (min-width: 769px) {
  .settings-row-3 {
    grid-template-columns: 1fr 1fr;
  }
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 4px;
}

.select-field {
  flex: 1;
  padding: 8px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
  color: #111827;
  background: #ffffff;
  transition: all 0.2s ease;
}

.select-field:focus {
  outline: none;
  border-color: #008080;
  box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.1);
}

.range-container {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 12px;
}

.range-slider {
  flex: 1;
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  outline: none;
  cursor: pointer;
}

.range-slider::-webkit-slider-thumb {
  appearance: none;
  width: 16px;
  height: 16px;
  background: #008080;
  border-radius: 50%;
  cursor: pointer;
}

.range-slider::-moz-range-thumb {
  width: 16px;
  height: 16px;
  background: #008080;
  border-radius: 50%;
  cursor: pointer;
  border: none;
}

.range-value {
  font-weight: 600;
  color: #008080;
  font-size: 14px;
  min-width: 24px;
  text-align: center;
}

.categories-grid {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 8px;
  max-height: 120px;
  overflow-y: auto;
  padding: 12px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  background: #f9fafb;
}

.category-checkbox {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 8px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 13px;
}

.category-checkbox:hover {
  background: #f0fdfa;
  border-color: #008080;
}

.category-checkbox input[type="checkbox"] {
  margin: 0;
  cursor: pointer;
}

.category-name {
  font-weight: 500;
  color: #374151;
}

.category-count {
  color: #6b7280;
  font-size: 11px;
}

.form-hint {
  color: #6b7280;
  font-size: 12px;
  line-height: 1.4;
  margin-top: 4px;
}

.form-hint.content-topics-hint {
  margin-left: 7.5rem;
}

.model-hint {
  margin-left: 4.4rem;
  margin-top: 4px;
}

.generation-summary {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border: 1px solid #bae6fd;
  border-radius: 8px;
  padding: 16px;
}

.summary-card h3 {
  color: #0369a1;
  margin-bottom: 12px;
  font-size: 16px;
}

.summary-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 8px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 8px;
  background: rgba(255, 255, 255, 0.7);
  border-radius: 4px;
  font-size: 13px;
}

.summary-label {
  font-weight: 500;
  color: #075985;
}

.summary-value {
  font-weight: 600;
  color: #0c4a6e;
}

.button-group {
  display: flex;
  justify-content: center;
  margin-top: 12px;
}

.generate-btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.generate-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.generate-btn:disabled {
  background: #d1d5db;
  color: #9ca3af;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.progress-section {
  margin-top: 20px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.progress-header h3 {
  color: #1e293b;
  margin: 0;
  font-size: 16px;
}

.progress-text {
  color: #64748b;
  font-size: 13px;
}

.progress-bar {
  width: 100%;
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 12px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #008080, #20b2aa);
  transition: width 0.3s ease;
}

.current-topic {
  text-align: center;
  color: #475569;
  font-style: italic;
  font-size: 13px;
}

.results-section {
  margin-top: 20px;
}

.results-section h3 {
  color: #1e293b;
  margin-bottom: 16px;
  font-size: 18px;
}

.results-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 12px;
}

.result-card {
  padding: 16px;
  border-radius: 8px;
  border: 2px solid;
}

.result-success {
  background: #f0fdf4;
  border-color: #22c55e;
}

.result-error {
  background: #fef2f2;
  border-color: #ef4444;
}

.result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.result-header h4 {
  margin: 0;
  color: #1e293b;
  font-size: 14px;
}

.result-status {
  font-weight: 600;
  font-size: 12px;
}

.result-success .result-status {
  color: #16a34a;
}

.result-error .result-status {
  color: #dc2626;
}

.result-message {
  color: #64748b;
  margin-bottom: 12px;
  font-size: 13px;
}

.result-posts {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.result-post {
  padding: 4px 8px;
  background: rgba(255, 255, 255, 0.7);
  border-radius: 4px;
}

.post-link {
  color: #008080;
  text-decoration: none;
  font-weight: 500;
  font-size: 13px;
}

.post-link:hover {
  text-decoration: underline;
}
</style>
