export function toCZK(number: number | null): string {
  if (number == null) {
    return "";
  }
  const normalizedNumber = number / 100_000;
  return Intl.NumberFormat("cs", {
    style: "currency",
    currency: "CZK",
    currencyDisplay: "code",
  }).format(normalizedNumber);
}
