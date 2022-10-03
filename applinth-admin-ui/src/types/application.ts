export type ApplicationDetailRaw = {
  name: string;
  publicName: string;
  description: [
    {
      lang: string;
      text: string;
    }
  ];
  logo: string | null;
  categories: string[];
};

export type ApplicationDetail = {
  name: string;
  publicName: string;
  description: string | null;
  logo: string | null;
  categories: string[];
};

export type IndexedApplicationDetail = {
  [key: string]: ApplicationDetail;
};
