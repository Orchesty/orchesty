/**
 * Pretty-print a JSON-serializable value with 2-space indentation.
 */
export const formatJson = (obj: unknown): string => {
  return JSON.stringify(obj, null, 2)
}
