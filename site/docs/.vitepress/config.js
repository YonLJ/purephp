export default {
  title: 'PurePHP',
  description: 'A PHP Template Engine inspired by ReactJS',
  base: '/purephp/',
  defaultLocale: 'en',
  locales: {
    root: {
      label: 'English',
      lang: 'en-US',
      themeConfig: {
        nav: [
          { text: 'Home', link: '/' },
          { text: 'Guide', link: '/guide/' },
          { text: 'API', link: '/api/' }
        ]
      }
    },
    zh: {
      label: '简体中文',
      lang: 'zh-CN',
      themeConfig: {
        nav: [
          { text: '首页', link: '/zh/' },
          { text: '指南', link: '/zh/guide/' },
          { text: 'API', link: '/zh/api/' }
        ]
      }
    }
  },
  themeConfig: {
    sidebar: {
      '/zh/guide/': [
        {
          text: '介绍',
          items: [
            { text: '什么是 PurePHP?', link: '/zh/guide/' },
            { text: '快速开始', link: '/zh/guide/getting-started' }
          ]
        },
        {
          text: '基础',
          items: [
            { text: '基本概念', link: '/zh/guide/concepts' },
            { text: '基本用法', link: '/zh/guide/basic-usage' },
            { text: '工具函数', link: '/zh/guide/utils' },
            { text: '组件', link: '/zh/guide/components' },
            { text: '属性', link: '/zh/guide/props' },
            { text: '事件', link: '/zh/guide/events' }
          ]
        },
        {
          text: '集成',
          items: [
            { text: 'HTMX', link: '/zh/guide/htmx' },
            { text: 'TailwindCSS', link: '/zh/guide/tailwindcss' }
          ]
        },
        {
          text: 'API 参考',
          items: [
            { text: '核心类', link: '/zh/api/' }
          ]
        }
      ],
      '/zh/api/': [
        {
          text: 'API 参考',
          items: [
            { text: '核心类', link: '/zh/api/' }
          ]
        }
      ],

      '/guide/': [
        {
          text: 'Introduction',
          items: [
            { text: 'What is PurePHP?', link: '/guide/' },
            { text: 'Quick Start', link: '/guide/getting-started' }
          ]
        },
        {
          text: 'Basics',
          items: [
            { text: 'Core Concepts', link: '/guide/concepts' },
            { text: 'Basic Usage', link: '/guide/basic-usage' },
            { text: 'Utility Functions', link: '/guide/utils' },
            { text: 'Components', link: '/guide/components' },
            { text: 'Props', link: '/guide/props' },
            { text: 'Events', link: '/guide/events' }
          ]
        },
        {
          text: 'Integration',
          items: [
            { text: 'HTMX', link: '/guide/htmx' },
            { text: 'TailwindCSS', link: '/guide/tailwindcss' }
          ]
        },
        {
          text: 'API Reference',
          items: [
            { text: 'Core Classes', link: '/api/' }
          ]
        }
      ],
      '/api/': [
        {
          text: 'API Reference',
          items: [
            { text: 'Core Classes', link: '/api/' }
          ]
        }
      ],

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
