export function toCZK(number: number | null): string {
  if (number == null) {
    return "";
  }
  return Intl.NumberFormat("cs", { style: "currency", currency: "CZK" }).format(
    number
  );
}
