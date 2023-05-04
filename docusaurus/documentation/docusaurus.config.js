const lightCodeTheme = require('prism-react-renderer').themes.github;
const darkCodeTheme = require('prism-react-renderer').themes.dracula;

/** @type {import('@docusaurus/types').DocusaurusConfig} */
module.exports = {
  title: 'Integration and Orchestration layer',
  tagline: 'Orchesty documentation',
  url: 'https://www.hanaboso.com',
  baseUrl: '/',
  onBrokenLinks: 'throw',
  onBrokenMarkdownLinks: 'warn',
  favicon: 'img/favicon.ico',
  organizationName: 'Orchesty/orchesty', // Usually your GitHub org/username.
  projectName: 'Orchesty', // Usually your repo name.
  themeConfig: {
    colorMode: {
      defaultMode: 'light',
      disableSwitch: true,
      respectPrefersColorScheme: false,
    },
    navbar: {
      // title: 'Docs',
      logo: {
        alt: 'Orchesty logo',
        src: 'img/Orch_logo_big.svg',
        srcDark: 'img/Orch_logo_big.svg',
      },
      items: [
        {
         type: 'docsVersionDropdown',
         docId: 'intro',
         position: 'right',
         label: 'Version',
        },
        {
          href: 'https://github.com/Orchesty',
          label: 'GitHub',
          position: 'right',
        },
      ],
    },
    footer: {
      style: 'light',
      copyright: `Copyright © ${new Date().getFullYear()} Orchesty.`,
    },
    prism: {
      theme: lightCodeTheme,
      darkTheme: darkCodeTheme,
      additionalLanguages: ['php'],
    },
  },
  presets: [
    [
      '@docusaurus/preset-classic',
      {
        docs: {
          routeBasePath: '/',
          includeCurrentVersion: true,
          sidebarPath: require.resolve('./sidebars.js'),
          // Please change this to your repo.
          lastVersion: 'current',
          versions: {
            current: {
              label: 'Current',
              path: '',
            },
            '1.0.6': {
              label: '1.0.6',
              path: '1.0.6',
            }
          },
        },
        theme: {
          customCss: require.resolve('./src/css/custom.css'),
        },
      },
    ],
  ],
  customFields: {
    version: ''
  }
};
