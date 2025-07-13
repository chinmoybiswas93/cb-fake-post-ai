<template>
  <div class="ai-generate-container">
    <div class="ai-generate-section">
      <h2 class="section-title">AI Content Generation</h2>
      
      <form @submit.prevent="generateAIPosts" class="generation-form">
        <!-- Topics Section -->
        <div class="form-section">
          <div class="form-row">
            <label class="form-label">Content Topics</label>
            <div class="topics-container">
              <div 
                v-for="(topic, index) in topics" 
                :key="index" 
                class="topic-input-group"
              >
                <input 
                  type="text" 
                  class="topic-field" 
                  v-model="topics[index]"
                  :placeholder="`Topic ${index + 1} (e.g., Technology trends, Health tips)`"
                  :disabled="isGenerating"
                />
                <button 
                  type="button" 
                  class="remove-topic-btn"
                  @click="removeTopic(index)"
                  v-if="topics.length > 1"
                  :disabled="isGenerating"
                >
                  ✕
                </button>
              </div>
              <button 
                type="button" 
                class="add-topic-btn"
                @click="addTopic"
                :disabled="isGenerating || topics.length >= 10"
              >
                + Add Topic
              </button>
            </div>
          </div>
          <small class="form-hint">
            Add multiple topics to generate varied content. Each topic will create posts based on your settings below.
          </small>
        </div>

        <!-- Generation Settings -->
        <div class="settings-grid">
          <!-- Content Style -->
          <div class="form-section">
            <div class="form-row">
              <label class="form-label">Content Style</label>
              <select class="select-field" v-model="contentStyle" :disabled="isGenerating">
                <option value="informative">Informative</option>
                <option value="casual">Casual</option>
                <option value="professional">Professional</option>
                <option value="creative">Creative</option>
                <option value="technical">Technical</option>
              </select>
            </div>
          </div>

          <!-- Number of Posts per Topic -->
          <div class="form-section">
            <div class="form-row">
              <label class="form-label">Posts per Topic</label>
              <div class="range-container">
                <input 
                  type="range" 
                  class="range-slider"
                  v-model="postsPerTopic" 
                  min="1" 
                  max="10"
                  :disabled="isGenerating"
                />
                <span class="range-value">{{ postsPerTopic }}</span>
              </div>
            </div>
          </div>

          <!-- Title Length -->
          <div class="form-section">
            <div class="form-row">
              <label class="form-label">Title Length (words)</label>
              <div class="range-container">
                <input 
                  type="range" 
                  class="range-slider"
                  v-model="titleLength" 
                  min="3" 
                  max="15"
                  :disabled="isGenerating"
                />
                <span class="range-value">{{ titleLength }}</span>
              </div>
            </div>
          </div>

          <!-- Content Length -->
          <div class="form-section">
            <div class="form-row">
              <label class="form-label">Content Length (paragraphs)</label>
              <div class="range-container">
                <input 
                  type="range" 
                  class="range-slider"
                  v-model="contentLength" 
                  min="1" 
                  max="8"
                  :disabled="isGenerating"
                />
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
              <label 
                v-for="category in categories" 
                :key="category.id" 
                class="category-checkbox"
              >
                <input 
                  type="checkbox" 
                  :value="category.id" 
                  v-model="selectedCategories"
                  :disabled="isGenerating"
                />
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
          <button 
            type="submit" 
            class="generate-btn" 
            :disabled="isGenerating || !canGenerate"
          >
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
          <div 
            v-for="result in generationResults" 
            :key="result.topic" 
            class="result-card"
            :class="{ 'result-success': result.success, 'result-error': !result.success }"
          >
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
              this.error(`Failed to generate posts for "${topic}": ${result.message}`);
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
.ai-generate-container {
  max-width: 1000px;
}

/* .ai-generate-section {
  padding: 24px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
} */

.section-title {
  font-size: 24px;
  font-weight: 600;
  color: #111827;
  margin-bottom: 32px;
  padding-bottom: 12px;
}

.generation-form {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.form-section {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 12px;
}

.form-row {
  display: flex;
  align-items: baseline;
  gap: 16px;
}

.form-label {
  font-size: 15px;
  font-weight: 600;
  color: #374151;
  min-width: 150px;
  flex-shrink: 0;
}

.topics-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.topic-input-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.topic-field {
  flex: 1;
  padding: 4px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
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
  padding: 8px 10px;
  border: 2px solid #ef4444;
  border-radius: 8px;
  background: #fef2f2;
  color: #dc2626;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 600;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.remove-topic-btn:hover:not(:disabled) {
  background: #ef4444;
  color: white;
}

.add-topic-btn {
  padding: 8px 16px;
  border: 2px dashed #008080;
  border-radius: 8px;
  background: #f0fdfa;
  color: #008080;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 600;
  align-self: flex-start;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.add-topic-btn:hover:not(:disabled) {
  background: #008080;
  color: white;
  border-style: solid;
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
}

.select-field {
  flex: 1;
  padding: 4px 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
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
  gap: 16px;
}

.range-slider {
  flex: 1;
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  outline: none;
  cursor: pointer;
}

.range-slider::-webkit-slider-thumb {
  appearance: none;
  width: 20px;
  height: 20px;
  background: #008080;
  border-radius: 50%;
  cursor: pointer;
}

.range-slider::-moz-range-thumb {
  width: 20px;
  height: 20px;
  background: #008080;
  border-radius: 50%;
  cursor: pointer;
  border: none;
}

.range-value {
  font-weight: 600;
  color: #008080;
  font-size: 16px;
  min-width: 30px;
  text-align: center;
}

.categories-grid {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
  max-height: 200px;
  overflow-y: auto;
  padding: 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: #f9fafb;
}

.category-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 12px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
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
  font-size: 12px;
}

.form-hint {
  color: #6b7280;
  font-size: 13px;
  line-height: 1.4;
  margin-left: 166px;
}

.generation-summary {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border: 1px solid #bae6fd;
  border-radius: 12px;
  padding: 20px;
}

.summary-card h3 {
  color: #0369a1;
  margin-bottom: 16px;
  font-size: 18px;
}

.summary-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 12px;
  background: rgba(255, 255, 255, 0.7);
  border-radius: 6px;
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
  margin-top: 16px;
}

.generate-btn {
  padding: 16px 32px;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.generate-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
}

.generate-btn:disabled {
  background: #d1d5db;
  color: #9ca3af;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.progress-section {
  margin-top: 32px;
  padding: 24px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.progress-header h3 {
  color: #1e293b;
  margin: 0;
}

.progress-text {
  color: #64748b;
  font-size: 14px;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 16px;
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
}

.results-section {
  margin-top: 32px;
}

.results-section h3 {
  color: #1e293b;
  margin-bottom: 20px;
  font-size: 20px;
}

.results-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 16px;
}

.result-card {
  padding: 20px;
  border-radius: 12px;
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
  margin-bottom: 12px;
}

.result-header h4 {
  margin: 0;
  color: #1e293b;
  font-size: 16px;
}

.result-status {
  font-weight: 600;
  font-size: 14px;
}

.result-success .result-status {
  color: #16a34a;
}

.result-error .result-status {
  color: #dc2626;
}

.result-message {
  color: #64748b;
  margin-bottom: 16px;
  font-size: 14px;
}

.result-posts {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.result-post {
  padding: 4px 12px;
  background: rgba(255, 255, 255, 0.7);
  border-radius: 6px;
}

.post-link {
  color: #008080;
  text-decoration: none;
  font-weight: 500;
  font-size: 14px;
}

.post-link:hover {
  text-decoration: underline;
}
</style>
