import { defineConfig } from 'vitepress'

export default defineConfig({
  lang: 'en-US',
  title: 'Resting',
  description: 'Typed REST resources, validation, and OpenAPI generation for Laravel.',
  base: '/resting/',
  cleanUrls: true,
  lastUpdated: true,

  head: [
    ['meta', { name: 'theme-color', content: '#3c8772' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Resting — Typed REST resources for Laravel' }],
    ['meta', { property: 'og:description', content: 'Typed REST resources, validation, and OpenAPI generation for Laravel.' }],
  ],

  themeConfig: {
    siteTitle: 'Resting',
    nav: [
      { text: 'Guide', link: '/guide/introduction', activeMatch: '/guide/' },
      {
        text: 'Reference',
        items: [
          { text: 'Resources', link: '/guide/resources' },
          { text: 'Fields', link: '/guide/fields' },
          { text: 'Validation', link: '/guide/validation' },
          { text: 'OpenAPI', link: '/guide/openapi' },
        ],
      },
      { text: 'GitHub', link: 'https://github.com/ebsp/resting' },
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Getting Started',
          items: [
            { text: 'Introduction', link: '/guide/introduction' },
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Quickstart', link: '/guide/quickstart' },
          ],
        },
        {
          text: 'Core Concepts',
          items: [
            { text: 'Resources', link: '/guide/resources' },
            { text: 'Fields', link: '/guide/fields' },
            { text: 'Validation', link: '/guide/validation' },
            { text: 'Marshalling', link: '/guide/marshalling' },
          ],
        },
        {
          text: 'Advanced',
          items: [
            { text: 'Polymorphic Resources', link: '/guide/polymorphism' },
            { text: 'Eloquent Integration', link: '/guide/eloquent' },
            { text: 'Routes & Macros', link: '/guide/routes' },
            { text: 'OpenAPI Generation', link: '/guide/openapi' },
          ],
        },
        {
          text: 'Reference',
          items: [
            { text: 'Configuration', link: '/guide/configuration' },
            { text: 'Testing', link: '/guide/testing' },
          ],
        },
      ],
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/ebsp/resting' },
    ],

    search: {
      provider: 'local',
    },

    editLink: {
      pattern: 'https://github.com/ebsp/resting/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2019-present Emil Büchler Seier Petersen',
    },
  },
})
