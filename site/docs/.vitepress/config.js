export default {
  title: 'PurePHP',
  description: 'A PHP Template Engine inspired by ReactJS',
  base: '/purephp/',
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
            { text: '快速开始', link: '/guide/getting-started' }
          ]
        },
        {
          text: '基础',
          items: [
            { text: '基本概念', link: '/guide/concepts' },
            { text: '基本用法', link: '/guide/basic-usage' },
            { text: '工具函数', link: '/guide/utils' },
            { text: '组件', link: '/guide/components' },
            { text: '属性', link: '/guide/props' },
            { text: '事件', link: '/guide/events' }
          ]
        },
        {
          text: '集成',
          items: [
            { text: 'HTMX', link: '/guide/htmx' },
            { text: 'TailwindCSS', link: '/guide/tailwindcss' }
          ]
        },
        {
          text: 'API 参考',
          items: [
            { text: '核心类', link: '/api/' }
          ]
        }
      ],
      '/api/': [
        {
          text: 'API 参考',
          items: [
            { text: '核心类', link: '/api/' }
          ]
        }
      ],

      '/en/guide/': [
        {
          text: 'Introduction',
          items: [
            { text: 'What is PurePHP?', link: '/en/guide/' },
            { text: 'Quick Start', link: '/en/guide/getting-started' }
          ]
        },
        {
          text: 'Basics',
          items: [
            { text: 'Core Concepts', link: '/en/guide/concepts' },
            { text: 'Basic Usage', link: '/en/guide/basic-usage' },
            { text: 'Utility Functions', link: '/en/guide/utils' },
            { text: 'Components', link: '/en/guide/components' },
            { text: 'Props', link: '/en/guide/props' },
            { text: 'Events', link: '/en/guide/events' }
          ]
        },
        {
          text: 'Integration',
          items: [
            { text: 'HTMX', link: '/en/guide/htmx' },
            { text: 'TailwindCSS', link: '/en/guide/tailwindcss' }
          ]
        },
        {
          text: 'API Reference',
          items: [
            { text: 'Core Classes', link: '/en/api/' }
          ]
        }
      ],
      '/en/api/': [
        {
          text: 'API Reference',
          items: [
            { text: 'Core Classes', link: '/en/api/' }
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
