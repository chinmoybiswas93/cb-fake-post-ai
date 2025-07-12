<template>
  <div class="cb-dashboard">
    <div class="cb-container">
      <header class="cb-header">
        <svg class="cb-logo" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="CB Fake Post Ai Logo">
          <circle cx="20" cy="20" r="20" fill="#008080"/>
          <path d="M7 33 L33 7" stroke="#fff" stroke-width="3.2" stroke-linecap="round"/>
          <path d="M32 20c0 6.627-5.373 12-12 12S8 26.627 8 20 13.373 8 20 8" stroke="#fff" stroke-width="2.2" fill="none"/>
        </svg>
        <h1>CB Fake Post Ai</h1>
      </header>
      <div class="cb-tabs-container">
        <nav class="cb-tabs">
          <button
            v-for="tab in tabs"
            :key="tab"
            :class="['cb-tab', { active: activeTab === tab }]"
            @click="activeTab = tab"
          >
            {{ tab }}
          </button>
        </nav>
      </div>
      <section class="cb-content">
        <component
          :is="currentTabComponent"
          v-bind="currentTabProps"
        />
      </section>
    </div>
    <ToastContainer />
  </div>
</template>

<script>
import SettingsTab from './tabs/SettingsTab.vue';
import ToolsTab from './tabs/ToolsTab.vue';
import HowToUseTab from './tabs/HowToUseTab.vue';
import AboutTab from './tabs/AboutTab.vue';
import ToastContainer from './components/ToastContainer.vue';

export default {
  name: "AdminApp",
  components: {
    SettingsTab,
    ToolsTab,
    HowToUseTab,
    AboutTab,
    ToastContainer
  },
  data() {
    return {
      tabs: ["Settings", "Tools", "How to use", "About"],
      activeTab: "Settings",
      form: {
        credit: "yes"
      }
    };
  },
  computed: {
    currentTabComponent() {
      switch (this.activeTab) {
        case 'Settings':
          return 'SettingsTab';
        case 'Tools':
          return 'ToolsTab';
        case 'How to use':
          return 'HowToUseTab';
        case 'About':
          return 'AboutTab';
        default:
          return 'SettingsTab';
      }
    },
    currentTabProps() {
      if (this.activeTab === 'Settings') {
        return {
          form: this.form
        };
      }
      return {};
    }
  }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: 'Inter', sans-serif;
}
/* Container for centering and containing all content */
.cb-container {
  max-width: 1200px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.cb-dashboard {
  font-family: 'Inter', sans-serif;
  min-height: 100vh;
  padding: 32px 0 0 0;
}
/* Header with logo and title */
.cb-header {
  border-bottom: 1px solid #e5e7eb;
  padding: 0px 0 16px 0;
  margin-bottom: 0;
  width: 100%;
  text-align: left;
  display: flex;
  align-items: center;
  gap: 18px;
}
.cb-logo {
  height: 38px;
  width: 38px;
  object-fit: contain;
  margin-right: 4px;
}
.cb-header h1 {
  font-size: 1.6rem;
  font-weight: 700;
  color: #22223b;
  margin: 0;
}
/* Tabs container to keep tabs within main layout */
.cb-tabs-container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 0 0 0;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.cb-tabs {
  display: flex;
  gap: 0;
  background: #f3f4f6;
  border-bottom: 1px solid #e5e7eb;
  width: 100%;
  box-sizing: border-box;
}
.cb-tab {
  background: none;
  border: none;
  outline: none;
  font-size: 1rem;
  font-weight: 600;
  color: #4b5563;
  padding: 16px 28px 12px 28px;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  transition: color 0.2s, border-bottom 0.2s;
}
.cb-tab.active {
  color: #008080;
  border-bottom: 3px solid #008080;
  background: #fff;
}
.cb-content {
  background: #fff;
  border-radius: 0 0 8px 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  width: 100%;
  max-width: 1200px;
  padding: 32px 40px 40px 40px;
  text-align: left;
}
.cb-content h2 {
  font-size: 1.3rem;
  font-weight: 600;
  margin-bottom: 12px;
  color: #22223b;
}
.cb-content p {
  color: #444;
  margin-bottom: 18px;
}
.cb-links {
  list-style: none;
  padding: 0;
  margin: 0 0 18px 0;
}
.cb-links li {
  margin-bottom: 6px;
}
.cb-links a {
  color: #008080;
  text-decoration: underline;
}
.cb-form {
  margin-top: 18px;
}
.cb-form-group {
  margin-bottom: 18px;
}
.cb-form-group label {
  display: block;
  font-weight: 500;
  margin-bottom: 6px;
}
.cb-form input[type="email"] {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 1rem;
  background: #f9fafb;
}
.cb-radio-group {
  display: flex;
  gap: 24px;
  margin-top: 6px;
}
.cb-radio-group label {
  font-weight: 400;
  color: #333;
}
.cb-btn {
  background: #008080;
  color: #fff;
  border: none;
  border-radius: 4px;
  padding: 10px 28px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.cb-btn:hover {
  background: #006666;
}
</style>
