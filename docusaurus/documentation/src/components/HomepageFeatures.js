import React from 'react';
import clsx from 'clsx';
import styles from './HomepageFeatures.module.css';
import Link from "@docusaurus/core/lib/client/exports/Link";

const FeatureList = [
    //{Svg: require('@site/static/img/Orch_logo.svg').default, title: 'Get started',description: 'Description'},
    {
        Svg: require('@site/static/img/stopwatch-svgrepo-com.svg').default,
        title: 'Get started',
        description: 'Download and install Orchesty. Read about Orchesty architecture for understand how to build your own integration service.',
        linkText: 'Get started with Orchesty',
        linkTo: '{{version}}/category/get-started'
    },
    {
        Svg: require('@site/static/img/study-svgrepo-com.svg').default,
        title: 'Tutorials',
        description: 'Learn step by step how to integrate services and orchestrate data processes between them.',
        linkText: 'Get started with Tutorials',
        linkTo: '{{version}}/category/tutorials'
    },
    {
        Svg: require('@site/static/img/dvi-connector-svgrepo-com.svg').default,
        title: 'SDKs',
        description: 'Using the SDK in microservices, you can easily orchestrate them through orchestration layer. You can write your own connectors for services.',
        linkText: 'Choose your SDK',
        linkTo: '{{version}}/get-started/SDK'
    },
    //{Svg: 'svg', title: 'Documentation',description: 'Description'}
];

function Feature({Svg, title, description, linkText, linkTo, version}) {
  return (
    <div className={clsx('col col--4')}>
      <div className="text--center">
        <Svg className={styles.featureSvg} alt={title} />
      </div>
      <div className="text--center padding-horiz--md">
        <h3>{title}</h3>
        <p>{description}</p>
          <p>
              <Link
                  className="features-link"
                  to={linkTo.replace('{{version}}', version)}>
                  {linkText}
              </Link>
          </p>
      </div>
    </div>
  );
}

export default function HomepageFeatures({siteConfig}) {
  const {customFields} = siteConfig

  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          {FeatureList.map((props, idx) => (
            <Feature key={idx} {...props} version={customFields.version} />
          ))}
        </div>
      </div>
    </section>
  );
}
