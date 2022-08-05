export interface TablePager {
  page: number;
  size: number;
  total: number | null;
  previous: number | null;
  next: number | null;
  last: number | null;
}

export interface TableState<Item = any> {
  items: Item[];
  filter: any;
  sorter: any;
  pager: TablePager;
  search: string | null;
}

export const createState = (): TableState => {
  return {
    items: [],
    filter: null,
    sorter: null,
    pager: {
      page: 1,
      size: 10,
      total: null,
      previous: null,
      next: null,
      last: null,
    },
    search: null,
  };
};
