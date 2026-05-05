# iDoklad Issued Invoice Data Model

API Endpoint: `POST https://api.idoklad.cz/v3/IssuedInvoices`

## Required Fields

| Field | Type | Description |
|---|---|---|
| `PartnerId` | `int` | Contact/customer ID (must exist in iDoklad) |
| `Description` | `string` | Invoice description / subject line |
| `DateOfIssue` | `DateTime` | Issue date (ISO 8601 format) |
| `DateOfMaturity` | `DateTime` | Due date (must be >= DateOfIssue) |
| `DateOfTaxing` | `DateTime` | Date of taxable supply |
| `PaymentOptionId` | `int` | Payment method ID (see Payment Options below) |
| `Items` | `array` | At least 1 invoice item required (see Items section) |

## Optional Fields

| Field | Type | Description |
|---|---|---|
| `CurrencyId` | `int` | Currency ID (default: account currency) |
| `NumericSequenceId` | `int` | Numeric sequence for auto-numbering |
| `DocumentSerialNumber` | `int` | Document serial number |
| `IsEet` | `bool` | Register in EET (electronic records of sales) |
| `IsIncomeTax` | `bool` | Include in income tax declaration |
| `AccountNumber` | `string(50)` | Bank account number |
| `BankId` | `int` | Bank ID |
| `Iban` | `string(50)` | IBAN |
| `Swift` | `string(11)` | SWIFT code |
| `ConstantSymbolId` | `int` | Constant symbol ID |
| `VariableSymbol` | `string(10)` | Variable symbol (payment reference) |
| `OrderNumber` | `string(25)` | External order number |
| `Note` | `string` | Internal note |
| `ItemsTextPrefix` | `string` | Text before items |
| `ItemsTextSuffix` | `string` | Text after items |
| `DiscountPercentage` | `decimal` | Invoice-level discount (0.00–99.99%) |
| `Tags` | `int[]` | Array of Tag IDs to associate |
| `ReportLanguage` | `enum` | Language: `CZ`, `SK`, `EN`, `DE` |
| `DateOfPayment` | `DateTime` | Payment date (if already paid) |
| `ExchangeRate` | `decimal` | Exchange rate for foreign currency |
| `ExchangeRateAmount` | `decimal` | Exchange rate amount |
| `HasVatRegimeOss` | `bool` | OSS VAT regime flag |
| `DeliveryAddressId` | `int` | Delivery address ID (from contact) |
| `ProformaInvoices` | `int[]` | Proforma invoice IDs to account |
| `SalesOrderId` | `int` | Sales order ID |
| `VatOnPayStatus` | `enum` | `Disabled`, `Enabled`, `InvoiceNeedsTaxing` |
| `VatReverseChargeCodeId` | `int` | Reverse charge code ID |

## Invoice Items

Each item in the `Items` array:

### Required Item Fields

| Field | Type | Description |
|---|---|---|
| `Name` | `string(200)` | Item name |
| `Amount` | `decimal` | Quantity |
| `UnitPrice` | `decimal` | Unit price |
| `PriceType` | `enum` | How the price is specified (see PriceType enum) |
| `VatRateType` | `enum` | VAT rate category (see VatRateType enum) |
| `IsTaxMovement` | `bool` | Tax movement flag |
| `DiscountPercentage` | `decimal` | Item-level discount (0.00–99.99%) |

### Optional Item Fields

| Field | Type | Description |
|---|---|---|
| `Code` | `string(20)` | Item code / SKU |
| `Unit` | `string(20)` | Unit of measure (ks, hod, etc.) |
| `PriceListItemId` | `int` | Link to price list item |
| `VatRate` | `decimal` | Explicit VAT rate in % |
| `VatCodeId` | `int` | VAT classification code ID |
| `DiscountName` | `string(200)` | Discount description |

## Enums

### PriceType

| Value | Name | Description |
|---|---|---|
| `0` | `WithVat` | Price includes VAT |
| `1` | `WithoutVat` | Price excludes VAT |
| `2` | `OnlyBase` | Only base price |

### VatRateType

| Value | Name | Description |
|---|---|---|
| `0` | `Reduced1` | First reduced rate (12%) |
| `1` | `Basic` | Basic rate (21%) |
| `2` | `Zero` | Zero rate (0%) |
| `3` | `Reduced2` | Second reduced rate (12%) |

### Payment Options

| ID | Name |
|---|---|
| `1` | Bank Transfer |
| `2` | Card |
| `3` | Cash |
| `4` | Cash on Delivery |
| `5` | Credit / Offset |
| `6` | Down Payment |
| `7` | Penny Compensation |
| `8` | Meal Voucher |
| `9` | PayPal |

## Helper Endpoints

| Endpoint | Description |
|---|---|
| `GET /IssuedInvoices/Default` | Pre-filled template with default values |
| `POST /IssuedInvoices/Recount` | Recalculate prices for items/currency |
| `GET /NumericSequences` | List numeric sequences |
| `GET /PaymentOptions` | List payment options |
| `GET /Currencies` | List currencies |
| `GET /Countries` | List countries |

## Filtering Issued Invoices (GET /IssuedInvoices)

Filter syntax: `filter=PropertyName~operator~value`

| Filter | Operators | Notes |
|---|---|---|
| `DateOfIssue` | `gt`, `lt`, `gte`, `lte` | Date range |
| `DateLastChange` | `gt`, `lt`, `gte`, `lte` | For incremental sync |
| `PaymentStatus` | `eq`, `ne` | `Unpaid`, `Paid`, `PartialPaid`, `Overpaid` |
| `PartnerId` | `eq` | Filter by customer |
| `TagIds` | contains | Filter by tag |
| `Exported` | `eq`, `ne` | `NotExported` (0), `Exported` (1), `Changed` (2) |
| `Description` | `ct`, `eq` | Text search |
| `DocumentNumber` | `ct`, `eq` | Invoice number search |

Pagination: `?page=1&pageSize=50&sort=DateOfIssue~desc`

## Example JSON

```json
{
  "PartnerId": 12345,
  "Description": "Faktura za konzultační služby",
  "DateOfIssue": "2026-02-07T00:00:00",
  "DateOfMaturity": "2026-02-21T00:00:00",
  "DateOfTaxing": "2026-02-07T00:00:00",
  "PaymentOptionId": 1,
  "IsEet": false,
  "IsIncomeTax": true,
  "OrderNumber": "ORD-2026-001",
  "Tags": [42],
  "Items": [
    {
      "Name": "Konzultační služby",
      "Amount": 10.0,
      "UnitPrice": 1500.00,
      "PriceType": 1,
      "VatRateType": 1,
      "IsTaxMovement": false,
      "DiscountPercentage": 0.0,
      "Unit": "hod"
    },
    {
      "Name": "Cestovné",
      "Amount": 1.0,
      "UnitPrice": 500.00,
      "PriceType": 1,
      "VatRateType": 1,
      "IsTaxMovement": false,
      "DiscountPercentage": 0.0,
      "Unit": "ks"
    }
  ]
}
```

## Contact Lookup

Before creating an invoice, ensure the contact exists. Use:

```
GET /Contacts?filter=IdentificationNumber~eq~{ICO}
```

If not found, create via `POST /Contacts` with at least `CompanyName`, `CountryId`.

The returned `Id` is used as `PartnerId` in the invoice.
