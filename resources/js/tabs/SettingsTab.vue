<template>
  <div class="settings-container">
    <form class="elegant-form" @submit.prevent="handleSubmit">
      <div class="two-column-layout">
        <!-- Left Column -->
        <div class="left-column">
          <div class="form-section">
            <div class="form-row">
              <label class="form-label">How many posts to generate?</label>
              <div class="range-input">
                <input type="number" class="range-field" v-model.number="settings.numPostsMin" min="1" placeholder="Min" />
                <span class="range-separator">–</span>
                <input type="number" class="range-field" v-model.number="settings.numPostsMax" :min="settings.numPostsMin" placeholder="Max" />
              </div>
            </div>
            <small class="form-hint">Set min and max, or the same value for a fixed amount.</small>
          </div>

          <div class="form-section">
            <div class="form-row">
              <label class="form-label">Post title size (words)</label>
              <div class="range-input">
                <input type="number" class="range-field" v-model.number="settings.titleMin" min="1" placeholder="Min" />
                <span class="range-separator">–</span>
                <input type="number" class="range-field" v-model.number="settings.titleMax" :min="settings.titleMin" placeholder="Max" />
              </div>
            </div>
          </div>

          <div class="form-section">
            <div class="form-row">
              <label class="form-label">Post content size (words)</label>
              <div class="range-input">
                <input type="number" class="range-field" v-model.number="settings.contentMin" min="1" placeholder="Min" />
                <span class="range-separator">–</span>
                <input type="number" class="range-field" v-model.number="settings.contentMax" :min="settings.contentMin" placeholder="Max" />
              </div>
            </div>
          </div>

          <div class="form-section">
            <div class="form-row">
              <label class="form-label">Show credit link below posts?</label>
              <div class="radio-group">
                <label class="radio-option">
                  <input type="radio" value="yes" v-model="form.credit" />
                  <span class="radio-label">Yes</span>
                </label>
                <label class="radio-option">
                  <input type="radio" value="no" v-model="form.credit" />
                  <span class="radio-label">No</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="right-column">
          <div class="form-section">
            <label class="form-label section-title">Categories</label>
            <div class="categories-container">
              <div v-if="loadingCategories" class="loading-message">
                Loading categories...
              </div>
              <div v-else-if="categories.length === 0" class="no-categories-message">
                No categories found. <a href="/wp-admin/edit-tags.php?taxonomy=category" target="_blank">Create some categories</a> first.
              </div>
              <div v-else class="checkbox-group">
                <label v-for="category in categories" :key="category.id" class="checkbox-option">
                  <input 
                    type="checkbox" 
                    :value="category.id" 
                    v-model="settings.categories"
                  />
                  <span class="checkbox-label">
                    {{ category.name }}
                    <span v-if="category.count > 0" class="post-count">({{ category.count }})</span>
                  </span>
                </label>
              </div>
            </div>
            <small class="form-hint">Select categories for the generated posts. If none selected, posts will be uncategorized.</small>
          </div>
        </div>
      </div>

      <div class="button-group">
        <button class="submit-btn" type="submit" :disabled="isSaving">
          {{ isSaving ? 'Saving...' : 'Save Changes' }}
        </button>
        <button class="generate-btn" type="button" @click="generatePosts" :disabled="isGenerating">
          {{ isGenerating ? 'Generating...' : 'Generate Posts' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import { useToast } from '../composables/useToast.js';

export default {
  name: "SettingsTab",
  props: {
    form: {
      type: Object,
      required: true
    }
  },
  setup() {
    const { success, error } = useToast();
    return { success, error };
  },
  data() {
    return {
      settings: {
        numPostsMin: 1,
        numPostsMax: 5,
        titleMin: 3,
        titleMax: 8,
        contentMin: 30,
        contentMax: 100,
        categories: []
      },
      categories: [],
      loadingCategories: false,
      isLoading: false,
      isSaving: false,
      isGenerating: false
    };
  },
  async mounted() {
    await Promise.all([
      this.loadSettings(),
      this.loadCategories()
    ]);
  },
  methods: {
    async loadSettings() {
      this.isLoading = true;
      try {
        const response = await fetch(window.SuitePressSettings.settingsRestUrl, {
          method: 'GET',
          headers: {
            'X-WP-Nonce': window.SuitePressSettings.nonce,
            'Content-Type': 'application/json'
          }
        });

        if (response.ok) {
          const result = await response.json();
          if (result.success && result.data) {
            this.settings = { ...this.settings, ...result.data };
            // Update form credit value if it exists
            if (result.data.credit) {
              this.form.credit = result.data.credit;
            }
          }
        }
      } catch (error) {
        console.error('Failed to load settings:', error);
        this.error('Failed to load settings. Please refresh the page.');
      } finally {
        this.isLoading = false;
      }
    },

    async loadCategories() {
      this.loadingCategories = true;
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
        this.error('Failed to load categories.');
      } finally {
        this.loadingCategories = false;
      }
    },

    async handleSubmit() {
      // Validate values
      if (
        this.settings.numPostsMin < 1 ||
        this.settings.numPostsMax < this.settings.numPostsMin ||
        this.settings.titleMin < 1 ||
        this.settings.titleMax < this.settings.titleMin ||
        this.settings.contentMin < 1 ||
        this.settings.contentMax < this.settings.contentMin
      ) {
        this.error('Please check your min/max values. Maximum values must be greater than or equal to minimum values.');
        return;
      }

      this.isSaving = true;
      
      try {
        const settingsData = {
          ...this.settings,
          credit: this.form.credit,
          categories: this.settings.categories || []
        };

        const response = await fetch(window.SuitePressSettings.settingsRestUrl, {
          method: 'POST',
          headers: {
            'X-WP-Nonce': window.SuitePressSettings.nonce,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(settingsData)
        });

        const result = await response.json();

        if (response.ok && result.success) {
          // Show different messages based on whether settings changed
          const message = result.message || 'Settings saved successfully!';
          this.success(message);
        } else {
          const errorMessage = result.message || 'Failed to save settings';
          this.error(errorMessage);
        }
      } catch (error) {
        console.error('Failed to save settings:', error);
        this.error('Network error: Failed to save settings');
      } finally {
        this.isSaving = false;
      }
    },

    async generatePosts() {
      // Validate settings first
      if (
        this.settings.numPostsMin < 1 ||
        this.settings.numPostsMax < this.settings.numPostsMin ||
        this.settings.titleMin < 1 ||
        this.settings.titleMax < this.settings.titleMin ||
        this.settings.contentMin < 1 ||
        this.settings.contentMax < this.settings.contentMin
      ) {
        this.error('Please check your settings values before generating posts.');
        return;
      }

      this.isGenerating = true;
      
      try {
        const response = await fetch(window.SuitePressSettings.generatePostsUrl, {
          method: 'POST',
          headers: {
            'X-WP-Nonce': window.SuitePressSettings.nonce,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.settings)
        });

        const result = await response.json();

        if (response.ok && result.success) {
          const message = result.message || 'Posts generated successfully!';
          this.success(message);
        } else {
          const errorMessage = result.message || 'Failed to generate posts';
          this.error(errorMessage);
        }
      } catch (error) {
        console.error('Failed to generate posts:', error);
        this.error('Network error: Failed to generate posts');
      } finally {
        this.isGenerating = false;
      }
    }
  }
};
</script>
<style scoped>
.settings-container {
  max-width: 900px;
}

.elegant-form {
  margin-top: 24px;
}

.two-column-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  margin-bottom: 32px;
}

@media (max-width: 768px) {
  .two-column-layout {
    grid-template-columns: 1fr;
    gap: 24px;
  }
}

.form-section {
  margin-bottom: 24px;
}

.section-title {
  display: block;
  font-size: 16px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 2px solid #e5e7eb;
}

.categories-container {
  margin-top: 12px;
}

.loading-message, .no-categories-message {
  color: #6b7280;
  font-style: italic;
  padding: 16px;
  text-align: center;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
}

.no-categories-message a {
  color: #008080;
  text-decoration: none;
}

.no-categories-message a:hover {
  text-decoration: underline;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: 300px;
  overflow-y: auto;
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: #f9fafb;
}

.checkbox-option {
  display: flex;
  align-items: center;
  cursor: pointer;
  font-size: 14px;
  color: #374151;
  padding: 4px 12px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.checkbox-option:hover {
  background: #ffffff;
}

.checkbox-option input[type="checkbox"] {
  margin-right: 10px;
  width: 16px;
  height: 16px;
  accent-color: #008080;
  cursor: pointer;
}

.checkbox-label {
  user-select: none;
  flex: 1;
}

.post-count {
  color: #6b7280;
  font-size: 12px;
  margin-left: 4px;
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
  letter-spacing: -0.01em;
  min-width: 200px;
  flex-shrink: 0;
}

.range-input {
  display: flex;
  align-items: center;
  gap: 12px;
}

.range-field {
  width: 90px;
  padding: 4px 8px;
  border: 1.5px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
  color: #111827;
  background: #ffffff;
  transition: all 0.2s ease;
  text-align: center;
}

.range-field:focus {
  outline: none;
  border-color: #008080;
  box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.1);
}

.range-separator {
  color: #6b7280;
  font-weight: 500;
  font-size: 16px;
}

.text-field {
  width: 100%;
  max-width: 320px;
  padding: 12px 14px;
  border: 1.5px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
  color: #111827;
  background: #ffffff;
  transition: all 0.2s ease;
}

.text-field:focus {
  outline: none;
  border-color: #008080;
  box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.1);
}

.text-field::placeholder {
  color: #9ca3af;
}

.radio-group {
  display: flex;
  gap: 24px;
}

.radio-option {
  display: flex;
  align-items: center;
  cursor: pointer;
  font-size: 14px;
  color: #374151;
}

.radio-option input[type="radio"] {
  margin-right: 8px;
  width: 16px;
  height: 16px;
  accent-color: #008080;
}

.radio-label {
  user-select: none;
}

.form-hint {
  color: #6b7280;
  font-size: 13px;
  line-height: 1.4;
  display: block;
}

.button-group {
  display: flex;
  gap: 16px;
  margin-top: 8px;
}

.submit-btn {
  background: linear-gradient(135deg, #008080 0%, #006666 100%);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 12px 32px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 128, 128, 0.2);
}

.generate-btn {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 12px 32px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
}

.generate-btn:hover {
  background: linear-gradient(135deg, #047857 0%, #065f46 100%);
  box-shadow: 0 4px 8px rgba(5, 150, 105, 0.3);
  transform: translateY(-1px);
}

.generate-btn:active {
  transform: translateY(0);
  box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
}

.generate-btn:disabled {
  background: #d1d5db;
  color: #9ca3af;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.generate-btn:disabled:hover {
  background: #d1d5db;
  transform: none;
  box-shadow: none;
}

.submit-btn:hover {
  background: linear-gradient(135deg, #006666 0%, #004d4d 100%);
  box-shadow: 0 4px 8px rgba(0, 128, 128, 0.3);
  transform: translateY(-1px);
}

.submit-btn:active {
  transform: translateY(0);
  box-shadow: 0 2px 4px rgba(0, 128, 128, 0.2);
}

.submit-btn:disabled {
  background: #d1d5db;
  color: #9ca3af;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.submit-btn:disabled:hover {
  background: #d1d5db;
  transform: none;
  box-shadow: none;
}
</style>
