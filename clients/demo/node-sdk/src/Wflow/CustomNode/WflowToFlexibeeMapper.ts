import { FLEXI_BEE_APPLICATION } from '@orchesty/connector-flexi-bee/dist/FexiBeeApplication';
import { IOutput as IInput } from '@orchesty/connector-wflow/dist/Connector/WflowGetDocumentConnector';
import { NAME as WFLOW_APP_NAME } from '@orchesty/connector-wflow/dist/WflowApplication';
import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import ProcessDto from '@orchesty/nodejs-sdk/dist/lib/Utils/ProcessDto';
import crypto from 'crypto';
import { FIRMA_KOD } from '../../FlexiBee/Connector/FlexiBeeFindFirmaKodConnector';
import { FakturaPayload, FirmaPayload, Payload, PolozkaFaktury } from '../../FlexiBee/types/payload';

export const NAME = `${WFLOW_APP_NAME}-to-${FLEXI_BEE_APPLICATION}-mapper`;

export default class WflowToFlexibeeMapper extends ACommonNode {

    public getName(): string {
        return NAME;
    }

    public processAction(dto: ProcessDto<IInput>): ProcessDto<IOutput> {
        const {
            id: wflowId,
            type: { kind },
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
        } = dto.getJsonData();
        const id = `ext:${wflowId}` as const;
        const kod = `WF-${new DataView(crypto.randomBytes(64).buffer).getBigUint64(0).toString(32)}` as const;
        const typDokl = this.getTypDokl(kind);
        const mena = `code:${currency}` as const;
        const polozkyFaktury = lines.length ? this.getPolozkyFaktury(lines as WflowLine[]) : undefined;
        const code = dto.getHeader(FIRMA_KOD);
        const firma = code ? `code:${code}` as const : undefined;

        const { ulice, mesto, psc } = this.parseAddress(partnerAddress);

        /* eslint-disable @typescript-eslint/naming-convention */
        if (firma) {
            return dto.setNewJsonData({
                winstrom: {
                    '@version': '1.0',
                    'faktura-prijata': [{
                        id,
                        kod,
                        typDokl,
                        cisDosle,
                        datSplat,
                        datVyst,
                        popis,
                        mena,
                        firma,
                        polozkyFaktury,
                    }],
                },
            });
        }

        return dto.setNewJsonData({
            winstrom: {
                '@version': '1.0',
                adresar: [{
                    kod: ic,
                    nazev,
                    ulice,
                    mesto,
                    psc,
                    ic,
                    dic,
                }],
                'faktura-prijata': [{
                    id,
                    kod,
                    typDokl,
                    cisDosle,
                    datSplat,
                    datVyst,
                    popis,
                    mena,
                    firma: `code:${ic}`,
                    polozkyFaktury,
                }],
            },
        });
        /* eslint-enable @typescript-eslint/naming-convention */
    }

    private getTypDokl(kind: string): `code:${string}` {
        switch (kind) {
            case 'IncomingInvoice':
                return 'code:FAKTURA';
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
            totalAmount: sumZkl,
        }) => ({
            nazev,
            mnozMj,
            typSzbDphK: vatTypeToTypSzbDphMap[vatType],
            szbDph,
            typPolozkyK: 'typPolozky.obecny',
            cenaMj,
            sumZkl,
        }));
    }

}

interface WflowLine {
    description: string;
    totalAmount: number;
    quantity: number;
    unitPrice: number;
    vatRate: number;
    vatType: 'Basic' | 'Exempt' | 'FirstReduced' | 'SecondReduced';
}

export type IOutput = Payload<FirmaPayload & FakturaPayload | FakturaPayload>;
