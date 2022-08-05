export const toLocalDateTime = (datetime: string | null): string => {
  if (datetime === null || datetime.length === 0) return "";

  return Intl.DateTimeFormat("cs").format(new Date(datetime));
};

export const toLocalDate = (datetime: string | null): string => {
  if (datetime === null || datetime.length === 0) return "";

  const options: Intl.DateTimeFormatOptions = {
    day: "numeric",
    month: "numeric",
    year: "numeric",
  };
  return Intl.DateTimeFormat("cs", options).format(new Date(datetime));
};

export const toMonthYear = (datetime: string | null): string => {
  if (datetime === null || datetime.length === 0) return "";

  const options: Intl.DateTimeFormatOptions = {
    month: "long",
    year: "numeric",
  };
  return Intl.DateTimeFormat("cs", options).format(new Date(datetime));
};

export const toLocalTime = (datetime: string | null): string => {
  if (datetime === null || datetime.length === 0) return "";

  const options: Intl.DateTimeFormatOptions = {
    hour: "numeric",
    minute: "numeric",
  };
  return Intl.DateTimeFormat("cs", options).format(new Date(datetime));
};

export const formatRangeOfDates = (
  from: Date | string,
  to: Date | string
): string => {
  if (from == null || to == null) return "";

  const options: Intl.DateTimeFormatOptions = {
    year: "numeric",
    month: "long",
    day: "numeric",
  };
  return (Intl.DateTimeFormat("cs", options) as any).formatRange(
    new Date(from),
    new Date(to)
  );
};
