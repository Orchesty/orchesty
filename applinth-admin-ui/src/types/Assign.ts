export type Assign<T1, T2> = Exclude<T1, keyof T2> & T2;
