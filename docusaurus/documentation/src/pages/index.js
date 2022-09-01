import React from 'react';
import clsx from 'clsx';
import Layout from '@theme/Layout';
import Link from '@docusaurus/Link';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import styles from './index.module.css';
import HomepageFeatures from '../components/HomepageFeatures';
import useBaseUrl from "@docusaurus/core/lib/client/exports/useBaseUrl";

function HomepageHeader() {
  const {siteConfig} = useDocusaurusContext();
  return (
    <header className={clsx(styles.heroBanner)}>
      <div className="container">
          <div className="main-logo">
            <MainLogo />
          </div>
        <h1 className="hero__title">{siteConfig.title}</h1>

        <div className={styles.buttons}>
          <Link
            className="button button--primary button--lg"
            to="/docs/get-started/installation">
            Download
          </Link>
        </div>
      </div>
    </header>
  );
}

const MainLogo = () => {
    const imgSrc = useBaseUrl('/img/Orch_logo_big.svg');
    return <img src={imgSrc} />;
};

export default function Home() {
  const {siteConfig} = useDocusaurusContext();
  return (
    <Layout
      title={`${siteConfig.title} docs`}
      description="Orchesty Documentation">
      <HomepageHeader />
      <main>
        <HomepageFeatures />
      </main>
    </Layout>
  );
}