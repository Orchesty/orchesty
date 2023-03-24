import { IAddressSearchQuery } from '../controllers/addresses';
import Address from '../entities/Address';
import { CollectionEnum } from '../enums/CollectionEnum';
import Mongo from '../storage/mongo/Mongo';
import BaseService from './BaseService';

export default class AddressService extends BaseService<Address, IAddressSearchQuery> {

    public constructor(db: Mongo) {
        super(db.getCloudCollection(CollectionEnum.ADDRESS));
    }

    protected mapRecordToExport(address: Address): Address {
        return {
            ...super.mapRecordToExport(address),
            tenantId: address.tenantId,
            email: address.email,
            phone: address.phone,
            firstname: address.firstname,
            surname: address.surname,
            street: address.street,
            city: address.city,
            postalCode: address.postalCode,
            companyName: address.companyName,
            countryId: address.countryId,
            identificationNumber: address.identificationNumber,
            defaultInvoiceMaturity: address.defaultInvoiceMaturity ?? null,
            isRegisteredForVatOnPay: address.isRegisteredForVatOnPay ?? null,
            isSendReminder: address.isSendReminder ?? null,
            title: address.title ?? null,
            url: address.url ?? null,
            vatIdentificationNumber: address.vatIdentificationNumber ?? null,
        };
    }

}
