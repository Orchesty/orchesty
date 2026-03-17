import { NAME as FLEXI_BEE_CREATE_FAKTURA_PRIJATA_NAME } from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeCreateFakturaPrijataConnector';
import { NAME as FLEXI_BEE_CREATE_ZAVAZEK_NAME } from '@orchesty/connector-flexi-bee/dist/Connector/FlexiBeeCreateZavazekConnector';
import { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { IOutput as IInput } from '@orchesty/connector-wflow/dist/Connector/WflowGetDocumentConnector';
import { NAME as WFLOW_APP_NAME } from '@orchesty/connector-wflow/dist/WflowApplication';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import { FIRMA_KOD } from '../../FlexiBee/Connector/FlexiBeeFindFirmaKodConnector';
import { FlexiBeeApplication } from '../../FlexiBee/FlexiBeeApplication';
import { FakturaPayload, FirmaPayload, Payload, PolozkaFaktury, ZavazekPayload } from '../../FlexiBee/types/payload';

export const NAME = `${WFLOW_APP_NAME}-document-to-${FLEXI_BEE_APPLICATION}-faktura-prijata-mapper`;

export const DOCUMENT_TYPE = 'documentType';
export const KIND_INCOMING_INVOICE = 'IncomingInvoice';
export const KIND_EXPENDITURE_CASH_SLIP = 'ExpenditureCashSlip';

export default class WflowDocumentToFlexibeeFakturaPrijataMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IOutput> {
        const data = dto.getJsonData();
        const {
            id: wflowId,
            type: { kind },
            invoiceType,
            accounting,
            variableSymbol: cisDosle,
            dueDate: datSplat,
            issueDate: datVyst,
            description: popis,
            currency,
            partnerName: nazev,
            partnerAddress,
            partnerIC: ic,
            partnerVAT: dic,
            lines = [], // eslint-disable-line @typescript-eslint/no-useless-default-assignment
        } = data;
        const id = `ext:${wflowId}` as const;
        const firmaKod = ic ?? dic ?? FlexiBeeApplication.createCode(nazev);
        const typDokl = this.getTypDokl(kind);
        const isPrijataProforma = kind === KIND_INCOMING_INVOICE && invoiceType === 'Proforma';
        let clenDph: `code:${string}` | undefined;
        let clenKonVykDph: `code:${string}` | undefined;

        if (accounting?.vatReturnLine?.code) {
            clenDph = `code:${accounting?.vatReturnLine?.code}` as const;
        } else if (isPrijataProforma) {
            clenDph = 'code:000P';
        }

        if (accounting?.vatControlStatementLine?.code) {
            clenKonVykDph = `code:${accounting?.vatControlStatementLine?.code}` as const;
        } else if (isPrijataProforma) {
            clenKonVykDph = 'code:0.0.';
        }

        const stredisko = accounting?.costCenter?.code
            ? `code:${accounting.costCenter.code}` as const : undefined;
        const typUcOp = accounting?.accountingRule?.code
            ? `code:${accounting.accountingRule.code}` as const : undefined;

        const mena = `code:${currency}` as const;
        const polozkyFaktury = lines.length
            ? this.getPolozkyFaktury(lines as WflowLine[])
            : this.getPolozkaFaktury(data);
        const code = dto.getHeader(FIRMA_KOD);
        const firma = code ? `code:${code}` as const : undefined;

        const { ulice, mesto, psc } = this.parseAddress(partnerAddress);

        dto.addHeader(DOCUMENT_TYPE, kind);

        /* eslint-disable @typescript-eslint/naming-convention */
        if (firma) {
            if (kind === KIND_INCOMING_INVOICE) {
                return dto.setNewJsonData({
                    winstrom: {
                        '@version': '1.0',
                        'faktura-prijata': [{
                            id,
                            typDokl,
                            clenDph,
                            clenKonVykDph,
                            cisDosle,
                            datSplat: datSplat ?? datVyst,
                            datVyst,
                            popis,
                            mena,
                            firma,
                            stredisko,
                            typUcOp,
                            polozkyFaktury,
                        }],
                    },
                }).setForceFollowers(FLEXI_BEE_CREATE_FAKTURA_PRIJATA_NAME);
            }

            return dto.setNewJsonData({
                winstrom: {
                    '@version': '1.0',
                    zavazek: [{
                        id,
                        typDokl,
                        clenDph,
                        clenKonVykDph,
                        cisDosle,
                        datSplat: datSplat ?? datVyst,
                        datVyst,
                        popis,
                        mena,
                        firma,
                        stredisko,
                        typUcOp,
                        polozkyZavazku: polozkyFaktury,
                    }],
                },
            }).setForceFollowers(FLEXI_BEE_CREATE_ZAVAZEK_NAME);
        }

        if (kind === KIND_INCOMING_INVOICE) {
            return dto.setNewJsonData({
                winstrom: {
                    '@version': '1.0',
                    adresar: [{
                        kod: firmaKod,
                        nazev,
                        ulice,
                        mesto,
                        psc,
                        ic,
                        dic,
                    }],
                    'faktura-prijata': [{
                        id,
                        typDokl,
                        clenDph,
                        clenKonVykDph,
                        cisDosle,
                        datSplat: datSplat ?? datVyst,
                        datVyst,
                        popis,
                        mena,
                        firma: `code:${firmaKod}` as const,
                        stredisko,
                        typUcOp,
                        polozkyFaktury,
                    }],
                },
            }).setForceFollowers(FLEXI_BEE_CREATE_FAKTURA_PRIJATA_NAME);
        }

        return dto.setNewJsonData({
            winstrom: {
                '@version': '1.0',
                adresar: [{
                    kod: firmaKod,
                    nazev,
                    ulice,
                    mesto,
                    psc,
                    ic,
                    dic,
                }],
                zavazek: [{
                    id,
                    typDokl,
                    clenDph,
                    clenKonVykDph,
                    cisDosle,
                    datSplat: datSplat ?? datVyst,
                    datVyst,
                    popis,
                    mena,
                    firma: `code:${firmaKod}` as const,
                    stredisko,
                    typUcOp,
                    polozkyZavazku: polozkyFaktury,
                }],
            },
        }).setForceFollowers(FLEXI_BEE_CREATE_ZAVAZEK_NAME);
        /* eslint-enable @typescript-eslint/naming-convention */
    }

    private getTypDokl(kind: string): `code:${string}` {
        switch (kind) {
            case KIND_INCOMING_INVOICE:
                return 'code:FAKTURA';
            case KIND_EXPENDITURE_CASH_SLIP:
                return 'code:OST. ZÁVAZKY';
            default:
                throw new Error(`Unsupported document kind [${kind}]`);
        }
    }

    private parseAddress(address: string): { ulice: string; mesto: string; psc: string } {
        const result = { ulice: '', mesto: '', psc: '' };

        let normalized = address.replace(/\s+/g, ' ');
        normalized = normalized.replace(/,\s/g, ',');

        const pscMatch = /\b(?<first>\d{3})\s?(?<second>\d{2})\b/g.exec(normalized);
        if (pscMatch) {
            result.psc = `${pscMatch.groups?.first}${pscMatch.groups?.second}`;
            normalized = normalized.replace(pscMatch[0], '').replace(/\s+/g, ' ');
        }

        const parts = normalized.split(',').map((p) => p.trim()).filter(Boolean);

        const streetIndex = parts.findIndex((p) => /\d/.test(p));
        if (streetIndex !== -1) {
            result.ulice = parts[streetIndex];
            parts.splice(streetIndex, 1);
        }

        if (parts.length > 0) {
            result.mesto = parts.join(' ');
        }

        return result;
    }

    private getPolozkyFaktury(lines: WflowLine[]): PolozkaFaktury[] {
        /* eslint-disable @typescript-eslint/naming-convention */
        const vatTypeToTypSzbDphMap = {
            Basic: 'typSzbDph.dphZakl',
            Exempt: 'typSzbDph.dphOsv',
            FirstReduced: 'typSzbDph.dphSniz',
            SecondReduced: 'typSzbDph.dphSniz2',
        } as const;
        /* eslint-enable @typescript-eslint/naming-convention */

        return lines.map(({
            description: nazev,
            quantity: mnozMj,
            unitPrice: cenaMj,
            vatRate: szbDph,
            vatType,
        }) => ({
            nazev,
            mnozMj,
            typSzbDphK: vatTypeToTypSzbDphMap[vatType],
            szbDph,
            typPolozkyK: 'typPolozky.obecny',
            cenaMj,
        }));
    }

    private getPolozkaFaktury(data: IInput): PolozkaFaktury[] {
        /* eslint-disable @typescript-eslint/naming-convention */
        const vatTypeToTypSzbDphMap = {
            Basic: 'typSzbDph.dphZakl',
            Exempt: 'typSzbDph.dphOsv',
            FirstReduced: 'typSzbDph.dphSniz',
            SecondReduced: 'typSzbDph.dphSniz2',
        } as const;
        /* eslint-enable @typescript-eslint/naming-convention */

        return [{
            nazev: data.description,
            mnozMj: 1,
            typSzbDphK: vatTypeToTypSzbDphMap[(data.vaTs?.[0]?.type ?? 'Basic') as keyof typeof vatTypeToTypSzbDphMap],
            szbDph: data.vaTs?.[0]?.rate ?? 21,
            typPolozkyK: 'typPolozky.obecny',
            cenaMj: data.taxExclusiveAmount,
        }];
    }

}

interface WflowLine {
    description: string;
    quantity: number;
    unitPrice: number;
    vatRate: number;
    vatType: 'Basic' | 'Exempt' | 'FirstReduced' | 'SecondReduced';
}

export type IOutput = Payload<
    FirmaPayload & FakturaPayload | FakturaPayload | FirmaPayload & ZavazekPayload | ZavazekPayload
>;
