export interface Payload<T> {
    winstrom: {
        ['@version']: string;
    } & T;
}

export interface FirmaPayload {
    adresar: {
        kod: string;
        nazev: string;
        ulice: string;
        mesto: string;
        psc: string;
        ic: string;
        dic: string;
    }[];
}

export interface PolozkaFaktury {
    nazev: string;
    mnozMj: number;
    typSzbDphK: 'typSzbDph.dphZakl' | 'typSzbDph.dphOsv' | 'typSzbDph.dphSniz' | 'typSzbDph.dphSniz2';
    szbDph: number;
    typPolozkyK: 'typPolozky.obecny';
    cenaMj: number;
    sumZklMen: number;
}

/* eslint-disable @typescript-eslint/naming-convention */
export interface FakturaPayload {
    'faktura-prijata': {
        id: `ext:${string}`;
        kod: `WF-${string}`;
        typDokl: `code:${string}`;
        clenDph?: `code:${string}`;
        clenKonVykDph?: `code:${string}`;
        cisDosle: string;
        datSplat: string;
        datVyst?: string;
        popis?: string;
        mena?: `code:${string}`;
        firma?: `code:${string}`;
        polozkyFaktury?: PolozkaFaktury[];
    }[];
}
/* eslint-enable @typescript-eslint/naming-convention */
