export default {
  title: 'PurePHP',
  description: 'A Virtual DOM-based PHP Template Engine',
  defaultLocale: 'zh-CN',
  locales: {
    root: {
      label: '简体中文',
      lang: 'zh-CN',
      themeConfig: {
        nav: [
          { text: '首页', link: '/' },
          { text: '指南', link: '/guide/' },
          { text: 'API', link: '/api/' }
        ]
      }
    },
    en: {
      label: 'English',
      lang: 'en-US',
      themeConfig: {
        nav: [
          { text: 'Home', link: '/en/' },
          { text: 'Guide', link: '/en/guide/' },
          { text: 'API', link: '/en/api/' }
        ]
      }
    }
  },
  themeConfig: {
    sidebar: {
      '/guide/': [
        {
          text: '介绍',
          items: [
            { text: '什么是 PurePHP?', link: '/guide/' },
            { text: '快速开始', link: '/guide/getting-started' },
            { text: '安装', link: '/guide/installation' }
          ]
        },
        {
          text: '基础',
          items: [
            { text: '基本概念', link: '/guide/concepts' },
            { text: '组件', link: '/guide/components' },
            { text: '属性', link: '/guide/props' },
            { text: '事件', link: '/guide/events' }
          ]
        },
        {
          text: '集成',
          items: [
            { text: 'HTMX 集成', link: '/guide/htmx' }
          ]
        }
      ],
      '/api/': [
        {
          text: '核心 API',
          items: [
            { text: '核心类', link: '/api/core' },
            { text: 'HTML 标签', link: '/api/html-tags' },
            { text: 'SVG 标签', link: '/api/svg-tags' }
          ]
        }
      ],
      '/en/guide/': [
        {
          text: 'Introduction',
          items: [
            { text: 'What is PurePHP?', link: '/en/guide/' },
            { text: 'Quick Start', link: '/en/guide/getting-started' },
            { text: 'Installation', link: '/en/guide/installation' }
          ]
        },
        {
          text: 'Basics',
          items: [
            { text: 'Core Concepts', link: '/en/guide/concepts' },
            { text: 'Components', link: '/en/guide/components' },
            { text: 'Props', link: '/en/guide/props' },
            { text: 'Events', link: '/en/guide/events' }
          ]
        },
        {
          text: 'Integration',
          items: [
            { text: 'HTMX Integration', link: '/en/guide/htmx' }
          ]
        }
      ],
      '/en/api/': [
        {
          text: 'Core API',
          items: [
            { text: 'Core Classes', link: '/en/api/core' },
            { text: 'HTML Tags', link: '/en/api/html-tags' },
            { text: 'SVG Tags', link: '/en/api/svg-tags' }
          ]
        }
      ]
    },
    footer: {
      message: 'Released under the MIT License',
      copyright: 'Copyright © 2024-present PurePHP'
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/YonLJ/purephp' }
    ],
    search: {
      provider: 'local'
    },
    langMenuLabel: 'Change language',
    returnToTopLabel: 'Back to top'
  }
}
