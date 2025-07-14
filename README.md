# CB Fake Post AI

A powerful WordPress plugin that leverages AI to generate fake posts for testing and development purposes. Built with modern technologies including Vue.js 3, Vite, and PHP 8+.

## 🚀 Major Features

### AI-Powered Content Generation
- **Automated Post Creation**: Generate realistic fake posts using AI
- **Customizable Content**: Configure post topics, categories, and styles
- **Bulk Generation**: Create multiple posts at once for rapid content population
- **Smart Categorization**: Automatically assign posts to appropriate categories

### Modern Admin Interface
- **Vue.js 3 Frontend**: Reactive and modern user interface
- **Tabbed Navigation**: Organized interface with multiple functional tabs:
  - **AI Generate**: Main content generation interface
  - **Settings**: Plugin configuration options
  - **Tools**: Additional utilities and features
  - **How To Use**: User guide and documentation
  - **About**: Plugin information and credits

### Developer-Friendly Architecture
- **REST API Integration**: Complete API endpoints for all functionality
- **Modern Build System**: Vite-powered development and production builds
- **Component-Based**: Modular Vue.js components for easy customization
- **PSR-4 Autoloading**: Organized PHP classes with proper namespacing

### WordPress Integration
- **Native WordPress Hooks**: Seamless integration with WordPress core
- **Admin Menu Integration**: Custom admin page with WordPress styling
- **Permission Management**: Proper capability checks for secure access
- **Translation Ready**: Internationalization support with text domain

## 📋 Requirements

- **WordPress**: Version 5.2+
- **PHP**: Version 7.2+
- **Node.js**: Version 18+
- **Composer**: Installed globally
- **NPM**: Installed globally

## 🛠️ Installation & Setup

1. **Install PHP dependencies**  
   ```bash
   composer install
   ```

2. **Dump autoload files**  
   ```bash
   composer dump-autoload
   ```

3. **Install Node modules**  
   ```bash
   npm install --include=dev
   ```

4. **Build production assets**  
   ```bash
   npm run build
   ```

5. **Activate the plugin** in WordPress Admin → Plugins

## 🎯 Usage

### Basic Usage
1. Navigate to **CB Fake Post AI** in your WordPress admin menu
2. Configure your AI settings in the **Settings** tab
3. Use the **AI Generate** tab to create fake posts:
   - Select target categories
   - Set number of posts to generate
   - Customize content parameters
   - Click "Generate Posts"
   

## 📝 License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## 👨‍💻 Author

**Chinmoy Biswas**  
GitHub: [@chinmoybiswas93](https://github.com/chinmoybiswas93)

---

For detailed usage instructions and troubleshooting, visit the **How To Use** tab within the plugin's admin interface.
